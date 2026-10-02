<?php

namespace App\Support;

use App\Models\School;
use Illuminate\Support\Facades\Cache;

/**
 * Source unique de l'identité visuelle d'un établissement (props Inertia + variables CSS).
 */
class SchoolBranding
{
    public const DEFAULT_COLORS = [
        'primary_color' => '#1E40AF',
        'secondary_color' => '#0F766E',
        'accent_color' => '#D97706',
    ];

    public static function cacheKey(int $schoolId): string
    {
        return "school:{$schoolId}:branding";
    }

    public static function for(?School $school): array
    {
        if (! $school) {
            return self::guest();
        }

        return Cache::remember(self::cacheKey($school->id), now()->addHour(), function () use ($school) {
            $s = $school->settings()->withoutGlobalScopes()->first();

            $primary = $s?->primary_color ?: self::DEFAULT_COLORS['primary_color'];

            return [
                'id' => $school->id,
                'name' => $s?->school_name ?: $school->name,
                'short_name' => $s?->short_name ?: $school->name,
                'slogan' => $s?->slogan,
                'logo_url' => $s?->logo_url,
                'primary_color' => $primary,
                'secondary_color' => $s?->secondary_color ?: self::DEFAULT_COLORS['secondary_color'],
                'accent_color' => $s?->accent_color ?: self::DEFAULT_COLORS['accent_color'],
                'primary_contrast' => self::contrast($primary),
                'phone' => $s?->phone,
                'email' => $s?->email,
                'address' => $s?->address,
            ];
        });
    }

    public static function guest(): array
    {
        return [
            'id' => null,
            'name' => config('app.name'),
            'short_name' => config('app.name'),
            'slogan' => null,
            'logo_url' => null,
            ...self::DEFAULT_COLORS,
            'primary_contrast' => self::contrast(self::DEFAULT_COLORS['primary_color']),
            'phone' => null,
            'email' => null,
            'address' => null,
        ];
    }

    /** Blanc ou quasi-noir selon la luminance (WCAG) pour garantir la lisibilité du texte sur la couleur primaire. */
    public static function contrast(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#FFFFFF';
        }

        $lin = function (int $c): float {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));
        $luminance = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);

        return $luminance > 0.4 ? '#111827' : '#FFFFFF';
    }
}
