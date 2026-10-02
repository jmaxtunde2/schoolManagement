<?php

namespace App\Models\Concerns;

use App\Models\School;
use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * À utiliser sur tout modèle métier possédant une colonne school_id.
 * NB : ne pas l'utiliser sur User (récursion lors de la résolution de auth()->user()).
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new SchoolScope);

        static::creating(function ($model) {
            if (empty($model->school_id) && auth()->user()?->school_id) {
                $model->school_id = auth()->user()->school_id;
            }
        });
    }

    /**
     * Laravel appelle automatiquement initialize{Trait}() sur chaque nouvelle instance.
     * Garantit que school_id reste assignable en masse côté serveur (seeders, actions,
     * tests) sans que chaque modèle ait à le déclarer individuellement dans $fillable.
     * La vraie barrière contre une valeur envoyée par le client est le Form Request,
     * qui ne valide jamais ce champ depuis la requête HTTP.
     */
    public function initializeBelongsToSchool(): void
    {
        $this->fillable([...$this->getFillable(), 'school_id']);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
