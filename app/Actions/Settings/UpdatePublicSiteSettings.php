<?php

namespace App\Actions\Settings;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Support\PublicSiteResults;
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
            'results' => PublicSiteResults::normalize(
                $data['results'] ?? [],
                $data['results_headline'] ?? null,
                $data['results_note'] ?? null,
            ),
            'results_headline' => filled($data['results_headline'] ?? null)
                ? trim((string) $data['results_headline'])
                : null,
            'results_note' => filled($data['results_note'] ?? null)
                ? trim((string) $data['results_note'])
                : null,
            'tuition_currency' => filled($data['tuition_currency'] ?? null)
                ? trim((string) $data['tuition_currency'])
                : null,
            'tuition_note' => filled($data['tuition_note'] ?? null)
                ? trim((string) $data['tuition_note'])
                : null,
            'tuition_fees' => $this->cleanFees($data['tuition_fees'] ?? []),
            'curriculum_intro' => filled($data['curriculum_intro'] ?? null)
                ? trim((string) $data['curriculum_intro'])
                : null,
            'curriculum' => $this->cleanCurriculum($data['curriculum'] ?? []),
            'calendar_intro' => filled($data['calendar_intro'] ?? null)
                ? trim((string) $data['calendar_intro'])
                : null,
            'calendar' => $this->cleanCalendar($data['calendar'] ?? []),
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

        // La relation a pu être mémorisée par l'autorisation : on la recharge
        // pour que la requête suivante voie immédiatement les nouvelles options.
        $school->unsetRelation('settings');

        $slug = $data['slug'] ?? null;
        if (is_string($slug) && $slug !== '' && $slug !== $school->slug) {
            $school->slug = $slug;
            $school->save();
        }

        $newHeroImage = $publicSite['hero_image_path'] ?? null;
        if ($oldHeroImage && $oldHeroImage !== $newHeroImage) {
            Storage::disk('public')->delete($oldHeroImage);
        }

        return $setting;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fees
     * @return array<int, array{label: string, amount: ?string, note: ?string}>
     */
    private function cleanFees(array $fees): array
    {
        return collect($fees)
            ->filter(fn ($fee) => is_array($fee) && trim((string) ($fee['label'] ?? '')) !== '')
            ->map(fn ($fee) => [
                'label' => trim((string) $fee['label']),
                'amount' => filled($fee['amount'] ?? null) ? trim((string) $fee['amount']) : null,
                'note' => filled($fee['note'] ?? null) ? trim((string) $fee['note']) : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{title: string, description: ?string}>
     */
    private function cleanCurriculum(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['title'] ?? '')) !== '')
            ->map(fn ($item) => [
                'title' => trim((string) $item['title']),
                'description' => filled($item['description'] ?? null)
                    ? trim((string) $item['description'])
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{label: string, date: ?string, description: ?string}>
     */
    private function cleanCalendar(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['label'] ?? '')) !== '')
            ->map(fn ($item) => [
                'label' => trim((string) $item['label']),
                'date' => filled($item['date'] ?? null) ? trim((string) $item['date']) : null,
                'description' => filled($item['description'] ?? null)
                    ? trim((string) $item['description'])
                    : null,
            ])
            ->values()
            ->all();
    }
}
