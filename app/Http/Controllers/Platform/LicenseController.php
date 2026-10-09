<?php

namespace App\Http\Controllers\Platform;

use App\Enums\LicenseType;
use App\Enums\LicenseStatus;
use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Payment;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LicenseController extends Controller
{
    public function index(Request $request): Response
    {
        $licenses = License::query()
            ->with(['school:id,name,slug,is_active'])
            ->when($request->query('search'), fn ($q, $s) => $q->whereHas('school', fn ($sq) => $sq->where('name', 'like', "%{$s}%")))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Licenses/Index', [
            'licenses' => $licenses,
            'filters' => [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
                'type' => $request->query('type'),
            ],
            'statusOptions' => LicenseStatus::options(),
            'typeOptions' => LicenseType::options(),
        ]);
    }

    public function show(License $license): Response
    {
        $license->load(['school:id,name,slug,is_active', 'payments:id,license_id,amount,status,reference,created_at']);

        return Inertia::render('Platform/Licenses/Show', [
            'license' => [
                'id' => $license->id,
                'school' => $license->school,
                'type' => $license->type->value,
                'type_label' => $license->type->label(),
                'status' => $license->status->value,
                'status_label' => $license->status->label(),
                'starts_at' => $license->starts_at?->format('d/m/Y'),
                'ends_at' => $license->ends_at?->format('d/m/Y'),
                'amount' => $license->amount,
                'currency' => $license->currency,
                'payment_reference' => $license->payment_reference,
                'is_expired' => $license->isExpired(),
                'days_remaining' => $license->daysRemaining(),
                'auto_renew' => $license->auto_renew,
                'created_at' => $license->created_at->format('d/m/Y H:i'),
            ],
            'payments' => $license->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'reference' => $p->reference,
                'created_at' => $p->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function renew(Request $request, License $license): RedirectResponse
    {
        abort_unless($license->status === LicenseStatus::Active || $license->status === LicenseStatus::Expired, 422, 'Seules les licences actives ou expirées peuvent être renouvelées.');

        $months = (int) $request->input('months', 12);
        $amount = (int) $request->input('amount', $license->amount);

        $endsAt = $license->ends_at && $license->ends_at->isFuture()
            ? $license->ends_at->copy()->addMonths($months)
            : now()->addMonths($months);

        $license->update([
            'ends_at' => $endsAt,
            'status' => LicenseStatus::Active,
            'amount' => $amount,
        ]);

        app(AuditService::class)->log('license.renewed', $license, [
            'months' => $months,
            'amount' => $amount,
            'new_ends_at' => $endsAt->format('Y-m-d'),
            'by' => $request->user()->id,
        ]);

        return back()->with('success', "Licence de « {$license->school->name} » renouvelée jusqu'au {$endsAt->format('d/m/Y')}.");
    }
}