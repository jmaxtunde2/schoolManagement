<?php

namespace App\Services\ExamBank;

use Illuminate\Support\Facades\Storage;

/**
 * Appose un filigrane CoriSchool sur les documents de la banque d'épreuves
 * avant leur téléchargement.
 *
 * Les formats image (JPEG/PNG/WebP) sont filigranés en mémoire via GD.
 * Les autres formats (PDF, office) sont servis tels quels : leur filigranage
 * nécessiterait une bibliothèque d'édition PDF dédiée.
 */
class ExamFileWatermarker
{
    /** @var array<int, string> */
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Retourne le chemin d'un fichier temporaire filigrané, ou null si le
     * filigranage n'est pas applicable au format.
     */
    public function watermark(string $disk, string $path, ?string $mime): ?string
    {
        if (! in_array($mime, self::IMAGE_MIMES, true) || ! extension_loaded('gd')) {
            return null;
        }

        $contents = Storage::disk($disk)->get($path);

        if ($contents === null) {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return null;
        }

        try {
            imagealphablending($image, true);
            $this->applyWatermark($image);

            $target = tempnam(sys_get_temp_dir(), 'corischool_wm_').$this->extension($mime);

            $this->save($image, $target, $mime);

            return $target;
        } finally {
            imagedestroy($image);
        }
    }

    private function applyWatermark(\GdImage $image): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $font = 5;
        $text = 'CoriSchool';
        $textWidth = imagefontwidth($font) * strlen($text);
        $lineHeight = imagefontheight($font);
        $tint = imagecolorallocatealpha($image, 255, 255, 255, 90);

        for ($y = 24; $y < $height; $y += $lineHeight + 70) {
            for ($x = 24; $x < $width; $x += $textWidth + 90) {
                imagestring($image, $font, $x, $y, $text, $tint);
            }
        }

        $bandHeight = min(30, $height);
        $band = imagecolorallocatealpha($image, 15, 23, 42, 35);
        imagefilledrectangle($image, 0, $height - $bandHeight, $width, $height, $band);

        $label = 'Document CoriSchool - usage pedagogique';
        $labelColor = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 3, 12, $height - $bandHeight + 9, $label, $labelColor);
    }

    private function save(\GdImage $image, string $target, string $mime): void
    {
        match ($mime) {
            'image/png' => imagepng($image, $target),
            'image/webp' => imagewebp($image, $target, 90),
            default => imagejpeg($image, $target, 90),
        };
    }

    private function extension(string $mime): string
    {
        return match ($mime) {
            'image/png' => '.png',
            'image/webp' => '.webp',
            default => '.jpg',
        };
    }
}
