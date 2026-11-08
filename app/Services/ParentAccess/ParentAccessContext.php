<?php

namespace App\Services\ParentAccess;

use App\Models\ParentGuardian;
use App\Models\School;
use Illuminate\Http\Request;

/**
 * Résout le parent et l'établissement selon le contexte :
 * - espace parent classique (utilisateur connecté avec le rôle parent) ;
 * - espace parent par code (session `parent_access`, sans compte utilisateur).
 */
class ParentAccessContext
{
    public function isParentAccess(Request $request): bool
    {
        return (bool) $request->attributes->get('parent_access');
    }

    public function parent(Request $request): ParentGuardian
    {
        if ($this->isParentAccess($request)) {
            $parent = $request->attributes->get('parent_access_parent');

            abort_unless($parent instanceof ParentGuardian, 403, 'Accès non autorisé');

            return $parent;
        }

        $parent = $request->user()->parentGuardian()->first();

        abort_unless($parent instanceof ParentGuardian, 403, "Aucun espace parent n'est associé à ce compte.");

        return $parent;
    }

    public function school(Request $request): School
    {
        if ($this->isParentAccess($request)) {
            $school = $request->attributes->get('parent_access_school');

            abort_unless($school instanceof School, 403, 'Accès non autorisé');

            return $school;
        }

        return $request->user()->school;
    }

    public function space(Request $request): string
    {
        return $this->isParentAccess($request) ? 'parent-access' : 'parent';
    }
}
