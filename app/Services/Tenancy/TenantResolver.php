<?php

namespace App\Services\Tenancy;

use App\Models\School;
use App\Models\SchoolDomain;
use Illuminate\Http\Request;

class TenantResolver
{
    public function resolve(Request $request): ?School
    {
        $host = strtolower($request->getHost());

        if ($host && ($domain = SchoolDomain::query()->where('domain', $host)->where('verified', true)->first())) {
            return $domain->school;
        }

        if ($user = auth()->user()) {
            return $user->school;
        }

        return null;
    }

    public function resolveBySlug(string $slug): ?School
    {
        return School::query()->where('slug', $slug)->first();
    }

    /**
     * Le domaine est-il revendiqué par un établissement, même non vérifié ?
     *
     * Permet de distinguer deux cas sur la route `/` :
     * - domaine revendiqué mais non vérifié -> établissement en cours de configuration,
     *   on n'affiche pas la landing CoriSchool à la place de son site ;
     * - aucun domaine revendiqué -> on est sur l'hôte de la plateforme, la landing
     *   CoriSchool (logiciel de gestion) est la page d'accueil-appropriate.
     */
    public function hasSchoolDomain(string $host): bool
    {
        return SchoolDomain::query()->where('domain', strtolower($host))->exists();
    }
}
