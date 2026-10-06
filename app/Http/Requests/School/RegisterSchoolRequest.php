<?php

namespace App\Http\Requests\School;

use App\Models\SchoolSetting;
use App\Support\BeninDepartments;
use App\Support\SchoolBranding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Validation de l'inscription d'un établissement.
 *
 * Le visiteur ne fournit aucun school_id : l'école, son année scolaire et son
 * administrateur sont créés ensemble par RegisterSchool, qui déduit school_id
 * du serveur. Aucun identifiant d'école ne peut donc être injecté par le client.
 */
class RegisterSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->input('email')) ? strtolower(trim($this->input('email'))) : $this->input('email'),
            'admin_email' => is_string($this->input('admin_email')) ? strtolower(trim($this->input('admin_email'))) : $this->input('admin_email'),
        ]);
    }

    public function rules(): array
    {
        $phone = ['nullable', 'string', 'regex:/^[0-9+\s().-]{6,30}$/'];

        return [
            // Identité de l'établissement
            'school_name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'school_type' => ['nullable', Rule::in(array_keys(SchoolSetting::SCHOOL_TYPES))],
            'founded_year' => ['nullable', 'integer', 'between:1900,'.now()->year],

            // Coordonnées
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'department' => ['required', Rule::in(array_keys(BeninDepartments::DEPARTMENTS))],
            'country' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\s().-]{6,30}$/'],
            'phone_secondary' => $phone,
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],

            // Année scolaire initiale
            'academic_year_name' => ['required', 'string', 'max:100'],
            'academic_year_starts_on' => ['required', 'date', 'after:2000-01-01'],
            'academic_year_ends_on' => ['required', 'date', 'after:academic_year_starts_on'],

            // Logo — SVG volontairement exclu (risque XSS), comme pour l'établissement.
            'logo' => [
                'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:min_width=64,min_height=64,max_width=2000,max_height=2000',
            ],

            // Compte administrateur
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_phone' => $phone,
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'school_name' => "nom de l'établissement",
            'short_name' => 'sigle',
            'school_type' => "type d'établissement",
            'founded_year' => 'année de création',
            'address' => 'adresse',
            'city' => 'ville',
            'department' => 'département',
            'phone' => 'téléphone principal',
            'phone_secondary' => 'téléphone secondaire',
            'contact_name' => 'contact administratif',
            'academic_year_name' => 'année scolaire',
            'academic_year_starts_on' => 'date de début de l’année scolaire',
            'academic_year_ends_on' => 'date de fin de l’année scolaire',
            'admin_name' => 'nom de l’administrateur',
            'admin_email' => 'email de l’administrateur',
            'admin_phone' => 'téléphone de l’administrateur',
            'admin_password' => 'mot de passe',
        ];
    }

    public function messages(): array
    {
        return [
            'logo.mimes' => 'Le logo doit être une image JPG, PNG ou WebP.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
            'logo.dimensions' => 'Le logo doit mesurer entre 64×64 et 2000×2000 pixels.',
            'admin_password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'department.in' => 'Sélectionnez un département béninien.',
        ];
    }

    /** Colonnes de school_settings, logo et année scolaire exclus. */
    public function schoolAttributes(): array
    {
        return Arr::except($this->validated(), [
            'logo',
            'admin_name',
            'admin_email',
            'admin_phone',
            'admin_password',
        ]);
    }

    /** @return array{name: string, email: string, password: string, phone: ?string} */
    public function adminAttributes(): array
    {
        return [
            'name' => $this->validated('admin_name'),
            'email' => $this->validated('admin_email'),
            'password' => $this->validated('admin_password'),
            'phone' => $this->validated('admin_phone'),
        ];
    }

    /** Couleurs par défaut de la plateforme, réinjectées à l'inscription. */
    public function defaultColors(): array
    {
        return SchoolBranding::DEFAULT_COLORS;
    }
}
