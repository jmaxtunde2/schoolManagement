<?php

namespace App\Services\ExamBank;

use App\Models\ExamResourceAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sert les fichiers de la banque après filigrane éventuel et nommage
 * CoriSchool. Les fichiers sont stockés sur un disque privé : ils ne sont
 * jamais exposés par une URL publique directe.
 */
class ExamFileDownloader
{
    public function __construct(private ExamFileWatermarker $watermarker) {}

    public function download(ExamResourceAttachment $attachment): BinaryFileResponse|StreamedResponse
    {
        $disk = Storage::disk($attachment->disk);
        $filename = $this->filename($attachment);

        if ($attachment->isImage()) {
            $watermarked = $this->watermarker->watermark(
                $attachment->disk,
                $attachment->path,
                $attachment->mime_type,
            );

            if ($watermarked !== null) {
                return response()->download($watermarked, $filename)->deleteFileAfterSend(true);
            }
        }

        return $disk->download($attachment->path, $filename);
    }

    private function filename(ExamResourceAttachment $attachment): string
    {
        $base = Str::slug(pathinfo($attachment->original_name, PATHINFO_FILENAME)) ?: 'document';
        $extension = pathinfo($attachment->original_name, PATHINFO_EXTENSION);
        $suffix = $attachment->isAnswerKey() ? 'corrige' : 'epreuve';

        return sprintf('CoriSchool-%s-%s%s', $suffix, $base, $extension ? ".{$extension}" : '');
    }
}
