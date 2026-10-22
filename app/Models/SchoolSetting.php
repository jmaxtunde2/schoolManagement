<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SchoolSetting extends Model
{
    use BelongsToSchool;

    public const SCHOOL_TYPES = [
        'maternelle' => 'École maternelle',
        'primaire' => 'École primaire',
        'college' => 'Collège',
        'lycee' => 'Lycée',
        'superieur' => 'Enseignement supérieur',
        'groupe_scolaire' => 'Groupe scolaire',
        'autre' => 'Autre',
    ];

    public const BENIN_DEPARTMENTS = [
        'Alibori',
        'Atacora',
        'Atlantique',
        'Borgou',
        'Collines',
        'Couffo',
        'Donga',
        'Littoral',
        'Mono',
        'Oueme',
        'Plateau',
        'Zou',
    ];

    // school_id n'a pas besoin d'être listé ici : BelongsToSchool le rend systématiquement
    // fillable pour tout modèle qui l'utilise (voir initializeBelongsToSchool()). La vraie
    // barrière de sécurité est UpdateSchoolSettingsRequest, jamais alimenté par le client.
    protected $fillable = [
        'school_name', 'short_name', 'slogan', 'description', 'school_type', 'founded_year',
        'address', 'city', 'department', 'country', 'phone', 'phone_secondary', 'email', 'website', 'contact_name',
        'logo_path', 'primary_color', 'secondary_color', 'accent_color', 'options', 'languages', 'programs', 'quartier',
        'timezone',
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 'founded_year' => 'integer', 'languages' => 'array', 'programs' => 'array'];
    }

    public static function foundedYearOptions(): array
    {
        $currentYear = now()->year;
        $years = [];
        for ($year = $currentYear + 10; $year >= $currentYear - 5; $year--) {
            $years[] = $year;
        }
        return $years;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
