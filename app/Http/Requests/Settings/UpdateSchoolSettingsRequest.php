<?php

namespace App\Http\Requests\Settings;

use App\Models\SchoolSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'établissement est toujours déduit de l'utilisateur connecté, jamais de la requête.
        return $this->user()->can('update', $this->user()->school->settingsOrNew());
    }

    protected function prepareForValidation(): void
    {
        foreach (['primary_color', 'secondary_color', 'accent_color'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => strtoupper(trim($this->input($key)))]);
            }
        }
    }

    public function rules(): array
    {
        $hex = ['required', 'string', 'regex:/^#[0-9A-F]{6}$/'];

        return [
            'school_name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'school_type' => ['nullable', Rule::in(array_keys(SchoolSetting::SCHOOL_TYPES))],
            'founded_year' => ['nullable', 'integer', 'between:1800,'.now()->year],

            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9+\s().-]{6,30}$/'],
            'phone_secondary' => ['nullable', 'string', 'regex:/^[0-9+\s().-]{6,30}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],

            'primary_color' => $hex,
            'secondary_color' => $hex,
            'accent_color' => $hex,

            // SVG volontairement exclu (risque XSS). Dimensions bornées pour rester raisonnables.
            'logo' => [
                'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:min_width=64,min_height=64,max_width=2000,max_height=2000',
            ],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'school_name' => "nom de l'établissement",
            'short_name' => 'nom court',
            'school_type' => "type d'établissement",
            'founded_year' => 'année de création',
            'phone' => 'téléphone',
            'phone_secondary' => 'téléphone secondaire',
            'contact_name' => 'contact administratif',
            'primary_color' => 'couleur primaire',
            'secondary_color' => 'couleur secondaire',
            'accent_color' => "couleur d'accent",
        ];
    }

    public function messages(): array
    {
        return [
            'logo.mimes' => 'Le logo doit être une image JPG, PNG ou WebP.',
            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',
            'logo.dimensions' => 'Le logo doit mesurer entre 64×64 et 2000×2000 pixels.',
        ];
    }
}
