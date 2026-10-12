<?php

namespace App\Services\Academic;

use App\Models\ReportCard;
use App\Models\School;
use App\Support\SchoolBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReportCardPdfService
{
    public function render(ReportCard $reportCard): string
    {
        $reportCard->loadMissing(['items.subject', 'student', 'classRoom', 'period', 'academicYear']);
        $snapshot = $reportCard->snapshot ?? [];

        // L'identité de l'établissement (nom, logo, coordonnées) suit toujours les
        // paramètres actuels, même pour un bulletin généré auparavant.
        $liveIdentity = SchoolBranding::documentIdentity(
            School::find($reportCard->school_id)
        );
        $snapshot['school'] = array_merge($snapshot['school'] ?? [], $liveIdentity);

        $logoPath = data_get($snapshot, 'school.logo_path');
        $logoAbsolutePath = $logoPath && Storage::disk('public')->exists($logoPath)
            ? Storage::disk('public')->path($logoPath)
            : null;

        return Pdf::loadView('pdf.report-card', [
            'reportCard' => $reportCard,
            'snapshot' => $snapshot,
            'logoAbsolutePath' => $logoAbsolutePath,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->output();
    }
}
