<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Services\Alerts\AlertNotifier;
use App\Services\Alerts\SchoolAlertService;
use App\Services\Assistant\AssistantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Génère les alertes de pilotage pour chaque établissement.
 *
 * Chaque alerte est agrégée et livrée en notification interne aux rôles
 * concernés (administrateurs, directeurs, comptables). Planifiée chaque
 * semaine (dimanche 16h00) afin de permettre au fondateur/directeur de
 * préparer son allocution du lundi après le drapeau.
 */
class GenerateSchoolAlerts extends Command
{
    protected $signature = 'corischool:generate-alerts
        {--force : Réémet les alertes même déjà livrées cette semaine}';

    protected $description = 'Génère et livre les alertes de pilotage pour chaque établissement';

    public function handle(AlertNotifier $notifier): int
    {
        $force = (bool) $this->option('force');
        $schoolIds = User::query()
            ->whereIn('role', [Role::Admin->value, Role::Director->value])
            ->whereNotNull('school_id')
            ->distinct()
            ->pluck('school_id');

        $delivered = 0;

        foreach ($schoolIds as $schoolId) {
            $contextUser = $this->contextUser((int) $schoolId);

            if ($contextUser === null) {
                continue;
            }

            $alerts = (new SchoolAlertService(AssistantContext::for($contextUser)))->collect();

            foreach ($alerts as $alert) {
                $recipients = $this->recipients((int) $schoolId, $alert['roles']);

                foreach ($recipients as $user) {
                    if ($notifier->deliver($user, $alert, $force)) {
                        $delivered++;
                    }
                }
            }

            $this->line(sprintf('École #%d : %d alerte(s) évaluée(s).', $schoolId, count($alerts)));
        }

        $this->info("{$delivered} alerte(s) livrée(s) cette semaine.");

        return self::SUCCESS;
    }

    private function contextUser(int $schoolId): ?User
    {
        $query = fn (Role $role) => User::query()
            ->where('school_id', $schoolId)
            ->where('role', $role->value)
            ->first();

        return $query(Role::Admin) ?? $query(Role::Director);
    }

    /**
     * @param  array<int, Role>  $roles
     * @return Collection<int, User>
     */
    private function recipients(int $schoolId, array $roles): Collection
    {
        return User::query()
            ->where('school_id', $schoolId)
            ->whereIn('role', array_map(fn (Role $role) => $role->value, $roles))
            ->get();
    }
}
