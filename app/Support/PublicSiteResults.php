<?php

namespace App\Support;

class PublicSiteResults
{
    /**
     * Normalise la liste des palmarès et assure la compatibilité avec
     * l'ancien champ unique results_headline / results_note.
     *
     * @return array<int, array{title: string, note: ?string}>
     */
    public static function normalize(mixed $results, ?string $legacyHeadline = null, ?string $legacyNote = null): array
    {
        $normalized = collect(is_array($results) ? $results : [])
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['title'] ?? '')) !== '')
            ->map(fn ($item) => [
                'title' => trim((string) $item['title']),
                'note' => filled($item['note'] ?? null) ? trim((string) $item['note']) : null,
            ])
            ->values()
            ->all();

        if ($normalized === [] && filled($legacyHeadline)) {
            $normalized[] = [
                'title' => trim((string) $legacyHeadline),
                'note' => filled($legacyNote) ? trim((string) $legacyNote) : null,
            ];
        }

        return $normalized;
    }
}
