<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restreint automatiquement toute requête à l'établissement de l'utilisateur connecté.
 * Sans utilisateur connecté (seeders, console, jobs) le scope n'est pas appliqué :
 * dans ces contextes, filtrer explicitement avec ->where('school_id', ...).
 */
class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = auth()->user()?->school_id;

        if ($schoolId !== null) {
            $builder->where($model->qualifyColumn('school_id'), $schoolId);
        }
    }
}
