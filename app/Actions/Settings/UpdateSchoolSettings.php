<?php

namespace App\Actions\Settings;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Support\SchoolBranding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateSchoolSettings
{
    public function handle(School $school, array $data, ?UploadedFile $logo = null, bool $removeLogo = false): SchoolSetting
    {
        $oldLogo = null;

        $setting = DB::transaction(function () use ($school, $data, $logo, $removeLogo, &$oldLogo) {
            $setting = $school->settings()->withoutGlobalScopes()->firstOrNew();
            $oldLogo = $setting->logo_path;

            $attributes = Arr::except($data, ['logo', 'remove_logo']);

            if ($logo) {
                $attributes['logo_path'] = $logo->store("schools/{$school->id}/logo", 'public');
            } elseif ($removeLogo) {
                $attributes['logo_path'] = null;
            }

            $setting->fill($attributes)->save();

            return $setting;
        });

        if ($oldLogo && $oldLogo !== $setting->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }

        Cache::forget(SchoolBranding::cacheKey($school->id));

        return $setting;
    }
}
