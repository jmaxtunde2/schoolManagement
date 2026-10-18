<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ExamResource;
use App\Models\User;

/**
 * Règles d'accès à la banque d'épreuves.
 *
 * - La plateforme administre les ressources centrales (school_id nul).
 * - Un établissement consulte les ressources centrales publiées et ses
 *   propres ressources, sans jamais pouvoir modifier une ressource
 *   appartenant à la plateforme.
 * - Les corrigés protégés ne sont accessibles qu'au personnel autorisé.
 */
class ExamResourcePolicy
{
    /** Rôles habilités à utiliser la banque au sein d'un établissement. */
    private const STAFF_ROLES = [
        Role::PlatformAdmin,
        Role::Admin,
        Role::Censeur,
        Role::Teacher,
    ];

    public function viewAny(User $user): bool
    {
        return in_array($user->role, self::STAFF_ROLES, true);
    }

    public function view(User $user, ExamResource $resource): bool
    {
        if (! in_array($user->role, self::STAFF_ROLES, true)) {
            return false;
        }

        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($resource->isPlatformResource()) {
            return $resource->isPublished();
        }

        return $resource->school_id === $user->school_id;
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, ExamResource $resource): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        // Une ressource de la plateforme ne peut jamais être modifiée
        // par un établissement.
        return ! $resource->isPlatformResource()
            && $user->isAdmin()
            && $resource->school_id === $user->school_id;
    }

    public function delete(User $user, ExamResource $resource): bool
    {
        return $this->update($user, $resource);
    }

    public function publish(User $user, ExamResource $resource): bool
    {
        return $user->isPlatformAdmin();
    }

    public function download(User $user, ExamResource $resource): bool
    {
        return $this->view($user, $resource);
    }

    /**
     * Téléchargement du corrigé : réservé au personnel autorisé,
     * jamais exposé aux élèves ou aux parents.
     */
    public function downloadAnswerKey(User $user, ExamResource $resource): bool
    {
        if (! in_array($user->role, [Role::PlatformAdmin, Role::Admin, Role::Censeur, Role::Teacher], true)) {
            return false;
        }

        return $this->view($user, $resource);
    }
}
