<?php

namespace App\Http\Requests\Settings;

use App\Support\ReservedSchoolSlugs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublicSiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->user()->school->settingsOrNew()
        );
    }

    public function rules(): array
    {
        return [
            'slug' => [
                'nullable', 'string', 'max:60',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(ReservedSchoolSlugs::all()),
                Rule::unique('schools', 'slug')->ignore($this->user()->school_id),
            ],
            'mission' => ['nullable', 'string', 'max:3000'],
            'vision' => ['nullable', 'string', 'max:3000'],
            'values' => ['nullable', 'array', 'max:5'],
            'values.*' => ['required', 'string', 'max:120'],
            'features' => ['nullable', 'array', 'max:6'],
            'features.*.title' => ['required', 'string', 'max:100'],
            'features.*.description' => ['nullable', 'string', 'max:500'],
            'results' => ['nullable', 'array', 'max:6'],
            'results.*.title' => ['required', 'string', 'max:160'],
            'results.*.note' => ['nullable', 'string', 'max:160'],
            'results_headline' => ['nullable', 'string', 'max:160'],
            'results_note' => ['nullable', 'string', 'max:160'],
            'tuition_currency' => ['nullable', 'string', 'max:12'],
            'tuition_note' => ['nullable', 'string', 'max:300'],
            'tuition_fees' => ['nullable', 'array', 'max:10'],
            'tuition_fees.*.label' => ['required', 'string', 'max:100'],
            'tuition_fees.*.amount' => ['nullable', 'string', 'max:40'],
            'tuition_fees.*.note' => ['nullable', 'string', 'max:200'],
            'curriculum_intro' => ['nullable', 'string', 'max:1000'],
            'curriculum' => ['nullable', 'array', 'max:8'],
            'curriculum.*.title' => ['required', 'string', 'max:100'],
            'curriculum.*.description' => ['nullable', 'string', 'max:800'],
            'calendar_intro' => ['nullable', 'string', 'max:1000'],
            'calendar' => ['nullable', 'array', 'max:20'],
            'calendar.*.label' => ['required', 'string', 'max:120'],
            'calendar.*.date' => ['nullable', 'string', 'max:120'],
            'calendar.*.description' => ['nullable', 'string', 'max:300'],
            'hero_image' => [
                'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120',
                'dimensions:min_width=640,min_height=360,max_width=4000,max_height=3000',
            ],
            'remove_hero_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => "L'adresse ne doit contenir que des minuscules, des chiffres et des tirets.",
            'slug.unique' => 'Cette adresse est déjà utilisée par un autre établissement.',
            'slug.not_in' => 'Cette adresse est réservée par la plateforme.',
        ];
    }
}
