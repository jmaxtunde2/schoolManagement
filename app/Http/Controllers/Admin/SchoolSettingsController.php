<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdateSchoolSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSchoolSettingsRequest;
use App\Models\SchoolSetting;
use App\Support\SchoolBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $school = $request->user()->school;
        $setting = $school->settingsOrNew();

        Gate::authorize('view', $setting);

        $brand = SchoolBranding::for($school);

        return Inertia::render('Settings/School', [
            'settings' => [
                'school_name' => $setting->school_name ?? $school->name,
                'short_name' => $setting->short_name,
                'slogan' => $setting->slogan,
                'description' => $setting->description,
                'school_type' => $setting->school_type,
                'founded_year' => $setting->founded_year,
                'address' => $setting->address,
                'city' => $setting->city,
                'country' => $setting->country,
                'phone' => $setting->phone,
                'phone_secondary' => $setting->phone_secondary,
                'email' => $setting->email,
                'website' => $setting->website,
                'contact_name' => $setting->contact_name,
                'primary_color' => $brand['primary_color'],
                'secondary_color' => $brand['secondary_color'],
                'accent_color' => $brand['accent_color'],
                'logo_url' => $setting->logo_url,
            ],
            'defaults' => SchoolBranding::DEFAULT_COLORS,
            'schoolTypes' => collect(SchoolSetting::SCHOOL_TYPES)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
        ]);
    }

    public function update(UpdateSchoolSettingsRequest $request, UpdateSchoolSettings $action): RedirectResponse
    {
        $action->handle(
            $request->user()->school,
            $request->safe()->except(['logo', 'remove_logo']),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        return back()->with('success', "Les paramètres de l'établissement ont été enregistrés.");
    }
}
