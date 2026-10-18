<?php

namespace App\Services\Documents;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Support\SchoolBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Centre de documents (phase 6).
 *
 * Génère les documents imprimables de l'établissement (certificats,
 * attestations, listes, rapports) en réutilisant l'identité visuelle de
 * l'école (SchoolBranding) et le moteur PDF déjà en place (dompdf).
 */
class SchoolDocumentService
{
    private const OPTIONS = ['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans'];

    /** Certificat ou attestation de scolarité pour un élève. */
    public function certificate(School $school, Student $student, string $kind = 'scolarite'): string
    {
        $student->loadMissing(['classRoom', 'guardians']);

        $isAttestation = $kind === 'attestation';

        return $this->render('pdf.certificate', [
            'identity' => $this->identity($school),
            'logoAbsolutePath' => $this->logo($school),
            'student' => $student,
            'title' => $isAttestation ? 'Attestation de scolarité' : 'Certificat de scolarité',
            'body' => $isAttestation
                ? sprintf(
                    "Nous attestons que l'élève %s%s, inscrit(e) en classe de %s, est régulièrement scolarisé(e) dans notre établissement pour l'année scolaire %s.",
                    $student->full_name,
                    $student->matricule ? " (matricule {$student->matricule})" : '',
                    $student->classRoom?->name ?? '—',
                    $this->currentYearName($school) ?? 'en cours',
                )
                : sprintf(
                    "Nous certifions que l'élève %s%s est régulièrement inscrit(e) en classe de %s au sein de notre établissement pour l'année scolaire %s.",
                    $student->full_name,
                    $student->matricule ? " (matricule {$student->matricule})" : '',
                    $student->classRoom?->name ?? '—',
                    $this->currentYearName($school) ?? 'en cours',
                ),
            'reference' => $this->reference($isAttestation ? 'ATT' : 'CERT', $student->id),
            'generatedAt' => now(),
        ]);
    }

    /**
     * Document tabulaire générique (listes et rapports).
     *
     * @param  array{title: string, subtitle?: ?string, meta?: array<string, string>, tables?: array<int, array<string, mixed>>, footer?: ?string}  $payload
     */
    public function table(School $school, array $payload): string
    {
        return $this->render('pdf.table-document', [
            'identity' => $this->identity($school),
            'logoAbsolutePath' => $this->logo($school),
            'title' => $payload['title'],
            'subtitle' => $payload['subtitle'] ?? null,
            'meta' => $payload['meta'] ?? [],
            'tables' => $payload['tables'] ?? [],
            'footer' => $payload['footer'] ?? null,
            'generatedAt' => now(),
        ]);
    }

    private function render(string $view, array $data): string
    {
        return Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', self::OPTIONS['isRemoteEnabled'])
            ->setOption('defaultFont', self::OPTIONS['defaultFont'])
            ->output();
    }

    private function identity(School $school): array
    {
        $identity = SchoolBranding::documentIdentity($school);
        $identity['name'] ??= $school->name;
        $identity['primary_color'] ??= SchoolBranding::DEFAULT_COLORS['primary_color'];

        return $identity;
    }

    private function logo(School $school): ?string
    {
        $path = SchoolBranding::documentIdentity($school)['logo_path'] ?? null;

        return $path && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->path($path)
            : null;
    }

    private function currentYearName(School $school): ?string
    {
        return AcademicYear::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('is_current', true)
            ->value('name');
    }

    private function reference(string $prefix, int $studentId): string
    {
        return sprintf('%s-%d-%s', $prefix, $studentId, now()->format('Ymd'));
    }
}
