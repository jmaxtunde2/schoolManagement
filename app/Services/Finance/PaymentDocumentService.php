<?php

namespace App\Services\Finance;

use App\Models\ContributionPayment;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Support\SchoolBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/** Génère les documents PDF liés à la scolarité (relevé parent, reçu de versement). */
class PaymentDocumentService
{
    /**
     * Relevé de scolarité remis au parent (liste des versements + totaux).
     *
     * @param  Collection<int, ContributionPayment>  $payments
     * @param  array{due: int, paid: int, remaining: int}  $totals
     */
    public function statement(School $school, ParentGuardian $parent, Collection $payments, array $totals): string
    {
        return Pdf::loadView('pdf.payment-statement', [
            'identity' => SchoolBranding::documentIdentity($school),
            'logoAbsolutePath' => $this->logoAbsolutePath($school),
            'parent' => $parent,
            'payments' => $payments,
            'totals' => $totals,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->output();
    }

    /** Reçu individuel pour un versement. */
    public function receipt(ContributionPayment $payment): string
    {
        $payment->loadMissing(['contribution.student.classRoom', 'contribution.academicYear', 'parent']);

        $school = School::find($payment->school_id);

        return Pdf::loadView('pdf.payment-receipt', [
            'identity' => $school ? SchoolBranding::documentIdentity($school) : [],
            'logoAbsolutePath' => $school ? $this->logoAbsolutePath($school) : null,
            'payment' => $payment,
            'contribution' => $payment->contribution,
            'generatedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->output();
    }

    private function logoAbsolutePath(School $school): ?string
    {
        $path = SchoolBranding::documentIdentity($school)['logo_path'] ?? null;

        return $path && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->path($path)
            : null;
    }
}
