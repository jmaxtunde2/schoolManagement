<?php

namespace App\Actions\Settings;

use App\Models\School;
use App\Models\SchoolSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdatePublicSiteSettings
{
    public function handle(
        School $school,
        array $data,
        ?UploadedFile $heroImage = null,
        bool $removeHeroImage = false
    ): SchoolSetting {
        $setting = $school->settings()->withoutGlobalScopes()->firstOrNew();
        $options = $setting->options ?? [];
        $publicSite = $options['public_site'] ?? [];
        $oldHeroImage = $publicSite['hero_image_path'] ?? null;

        $publicSite = array_merge($publicSite, [
            'mission' => $data['mission'] ?? null,
            'vision' => $data['vision'] ?? null,
            'values' => array_values(array_filter(
                $data['values'] ?? [],
                fn ($value) => trim((string) $value) !== ''
            )),
            'features' => array_values(array_filter(
                $data['features'] ?? [],
                fn ($feature) => trim((string) ($feature['title'] ?? '')) !== ''
            )),
        ]);

        if ($heroImage) {
            $publicSite['hero_image_path'] = $heroImage->store(
                "schools/{$school->id}/public-site/hero",
                'public'
            );
        } elseif ($removeHeroImage) {
            $publicSite['hero_image_path'] = null;
        }

        $options['public_site'] = $publicSite;
        $setting->options = $options;
        $setting->save();

        $newHeroImage = $publicSite['hero_image_path'] ?? null;
        if ($oldHeroImage && $oldHeroImage !== $newHeroImage) {
            Storage::disk('public')->delete($oldHeroImage);
        }

        return $setting;
    }
}