<?php

namespace App\Services\Assistant;

use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Contexte d'exécution du CoriSchool Assistant, construit exclusivement à
 * partir de l'utilisateur authentifié.
 *
 * C'est la seule source de vérité pour le `school_id` : aucun identifiant
 * d'établissement n'est accepté depuis la requête HTTP, ce qui garantit
 * qu'une école ne peut jamais consulter les données d'une autre.
 */
class AssistantContext
{
    /**
     * @param  array<int, int>|null  $allowedClassIds  null = toutes les classes de l'établissement.
     */
    private function __construct(
        public readonly User $user,
        public readonly int $schoolId,
        public readonly ?array $allowedClassIds,
        public readonly bool $canViewFinance,
        public readonly ?AcademicYear $academicYear,
        public readonly ?AcademicPeriod $currentPeriod,
    ) {}

    public static function for(User $user): self
    {
        $schoolId = (int) $user->school_id;
        $role = $user->role;

        // Le directeur primaire ne pilote que le primaire : toutes ses
        // réponses sont automatiquement restreintes à ces classes.
        $allowedClassIds = $role === Role::Director
            ? ClassRoom::query()
                ->primary()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : null;

        return new self(
            $user,
            $schoolId,
            $allowedClassIds,
            in_array($role, [Role::Admin, Role::Accountant], true),
            self::currentYear($schoolId),
            self::currentPeriod($schoolId),
        );
    }

    private static function currentYear(int $schoolId): ?AcademicYear
    {
        $query = AcademicYear::withoutGlobalScopes()->where('school_id', $schoolId);

        return (clone $query)->where('is_current', true)->first()
            ?? (clone $query)->orderByDesc('starts_on')->first();
    }

    private static function currentPeriod(int $schoolId): ?AcademicPeriod
    {
        $year = self::currentYear($schoolId);

        if ($year === null) {
            return null;
        }

        $query = AcademicPeriod::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id);

        return (clone $query)
            ->where('is_closed', false)
            ->whereDate('starts_at', '<=', now())
            ->whereDate('ends_at', '>=', now())
            ->orderBy('position')
            ->first()
            ?? (clone $query)->where('is_closed', false)->orderBy('position')->first()
            ?? (clone $query)->orderBy('position')->first();
    }

    /** Le périmètre est-il limité au primaire (directeur primaire) ? */
    public function isPrimaryScope(): bool
    {
        return $this->allowedClassIds !== null;
    }

    /**
     * Classes visibles par l'utilisateur (périmètre école ou primaire).
     *
     * @return Collection<int, ClassRoom>
     */
    public function classes(): Collection
    {
        return ClassRoom::query()
            ->when(
                $this->allowedClassIds !== null,
                fn ($query) => $query->whereIn('id', $this->allowedClassIds),
            )
            ->orderBy('name')
            ->get(['id', 'name', 'level', 'school_id']);
    }

    /** Mention de périmètre affichée sous les réponses. */
    public function scopeNote(): ?string
    {
        return $this->isPrimaryScope()
            ? 'Périmètre : classes du primaire de l\'établissement.'
            : null;
    }
}
