<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

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
            'mission' => ['nullable', 'string', 'max:3000'],
            'vision' => ['nullable', 'string', 'max:3000'],
            'values' => ['nullable', 'array', 'max:5'],
            'values.*' => ['required', 'string', 'max:120'],
            'features' => ['nullable', 'array', 'max:6'],
            'features.*.title' => ['required', 'string', 'max:100'],
            'features.*.description' => ['nullable', 'string', 'max:500'],
            'hero_image' => [
                'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120',
                'dimensions:min_width=640,min_height=360,max_width=4000,max_height=3000',
            ],
            'remove_hero_image' => ['nullable', 'boolean'],
        ];
    }
}