<?php

namespace App\Http\Controllers\Platform;

use App\Actions\School\ActivateSchool;
use App\Enums\DemoRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Demo\UpdateDemoRequestNotes;
use App\Http\Requests\Demo\UpdateDemoRequestStatus;
use App\Http\Requests\Platform\ActivateDemoRequest;
use App\Models\AuditLog;
use App\Models\DemoRequest;
use App\Services\AuditService;
use App\Services\Demo\DemoRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des demandes de démonstration — espace Super Admin Cori.
 *
 * Entité plateforme : aucun filtre `school_id`, l'accès est verrouillé par
 * `role:platform_admin` + DemoRequestPolicy.
 */
class DemoRequestController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', DemoRequest::class);

        $demoRequests = DemoRequest::query()
            ->search($request->query('search'))
            ->status($request->query('status'))
            ->with('handler:id,name,email')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $demoRequests->getCollection()->transform(
            fn (DemoRequest $demo) => $this->transform($demo)
        );

        $counts = DemoRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Platform/DemoRequests/Index', [
            'demoRequests' => $demoRequests,
            'filters' => [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
            ],
            'counts' => collect(DemoRequestStatus::cases())
                ->mapWithKeys(fn ($status) => [$status->value => (int) ($counts[$status->value] ?? 0)]),
            'statusOptions' => DemoRequestStatus::options(),
        ]);
    }

    public function show(DemoRequest $demoRequest): Response
    {
        Gate::authorize('view', $demoRequest);

        $demoRequest->load('handler:id,name,email');

        $activation = null;

        if ($demoRequest->isActivated()) {
            $activation = [
                'activated_at' => $demoRequest->activated_at->format('d/m/Y H:i'),
                'school_id' => $demoRequest->school_id,
            ];
        }

        return Inertia::render('Platform/DemoRequests/Show', [
            'demoRequest' => array_merge($this->transform($demoRequest), [
                'address' => $demoRequest->address,
                'preferred_demo_date_raw' => $demoRequest->preferred_demo_date?->format('Y-m-d'),
                'preferred_demo_time' => substr((string) $demoRequest->preferred_demo_time, 0, 5),
                'internal_notes' => $demoRequest->internal_notes,
                'handled_by' => $demoRequest->handled_by,
                'payment_requested_at' => $demoRequest->payment_requested_at?->format('d/m/Y H:i'),
                'payment_confirmed_at' => $demoRequest->payment_confirmed_at?->format('d/m/Y H:i'),
                'activated_at' => $demoRequest->activated_at?->format('d/m/Y H:i'),
                'initial_amount' => $demoRequest->initialAmount(),
            ]),
            'allowedTransitions' => collect(
                DemoRequestService::allowedTransitions($demoRequest->status)
            )->mapWithKeys(fn ($status) => [$status->value => $status->label()]),
            'statusOptions' => DemoRequestStatus::options(),
            'activation' => [
                'isApproved' => $demoRequest->status === DemoRequestStatus::Approved,
                'isPaymentRequested' => $demoRequest->isPaymentRequested(),
                'isPaymentConfirmed' => $demoRequest->isPaymentConfirmed(),
                'canBeActivated' => $demoRequest->canBeActivated(),
                'isActivated' => $demoRequest->isActivated(),
                'result' => $activation,
            ],
            'activationFlash' => session('activation'),
            'history' => AuditLog::query()
                ->where('auditable_type', DemoRequest::class)
                ->where('auditable_id', $demoRequest->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user' => $log->user?->name,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at->format('d/m/Y H:i'),
                ]),
        ]);
    }

    public function updateStatus(
        UpdateDemoRequestStatus $request,
        DemoRequest $demoRequest,
        DemoRequestService $service
    ): RedirectResponse {
        $attributes = $request->validated();
        $status = DemoRequestStatus::from($attributes['status']);

        if (! $service->canTransition($demoRequest->status, $status)) {
            return back()
                ->withInput()
                ->withErrors(['status' => 'Transition impossible : '
                    .$demoRequest->status->label().' → '.$status->label().'.']);
        }

        // La planification reporte le créneau souhaité sur la démo confirmée.
        if ($status === DemoRequestStatus::Scheduled) {
            $demoRequest->forceFill([
                'preferred_demo_date' => $attributes['preferred_demo_date'],
                'preferred_demo_time' => $attributes['preferred_demo_time']
                    ?? $demoRequest->preferred_demo_time,
            ])->save();
        }

        $service->transition(
            $demoRequest,
            $status,
            $request->user()->id,
            $attributes['internal_notes'] ?? null
        );

        return redirect()
            ->route('platform.demo-requests.show', $demoRequest)
            ->with('success', 'Demande « '.$demoRequest->school_name.' » : '.$status->label().'.');
    }

    public function updateNotes(UpdateDemoRequestNotes $request, DemoRequest $demoRequest): RedirectResponse
    {
        $demoRequest->update([
            'internal_notes' => $request->validated('internal_notes'),
        ]);

        return back()->with('success', 'Notes internes enregistrées.');
    }

    public function requestPayment(Request $request, DemoRequest $demoRequest): RedirectResponse
    {
        Gate::authorize('requestPayment', $demoRequest);

        abort_unless(
            $demoRequest->status === DemoRequestStatus::Approved
                && $demoRequest->payment_requested_at === null,
            422,
            'Le paiement ne peut être demandé que sur une demande approuvée.'
        );

        $demoRequest->forceFill([
            'payment_requested_at' => now(),
        ])->save();

        app(AuditService::class)->log('demo_request.payment_requested', $demoRequest, [
            'amount' => $demoRequest->initialAmount(),
            'currency' => config('cori.billing.currency'),
        ]);

        return back()->with('success', 'Paiement initial de '
            .number_format($demoRequest->initialAmount(), 0, ',', ' ')
            .' FCFA demandé à l\'établissement.');
    }

    public function confirmPayment(Request $request, DemoRequest $demoRequest): RedirectResponse
    {
        Gate::authorize('confirmPayment', $demoRequest);

        abort_unless(
            $demoRequest->isPaymentRequested()
                && $demoRequest->payment_confirmed_at === null,
            422,
            'Seul un paiement demandé peut être confirmé.'
        );

        $demoRequest->forceFill([
            'payment_confirmed_at' => now(),
            'payment_confirmed_by' => $request->user()->id,
        ])->save();

        app(AuditService::class)->log('demo_request.payment_confirmed', $demoRequest, [
            'amount' => $demoRequest->initialAmount(),
            'currency' => config('cori.billing.currency'),
            'confirmed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Paiement initial confirmé · '
            .number_format($demoRequest->initialAmount(), 0, ',', ' ')
            .' FCFA. L\'école peut maintenant être activée.');
    }

    public function activate(
        ActivateDemoRequest $request,
        DemoRequest $demoRequest,
        ActivateSchool $activate
    ): RedirectResponse {
        $result = $activate->handle($demoRequest, $request->validated());

        return redirect()
            ->route('platform.demo-requests.show', $demoRequest)
            ->with('activation', [
                'school_id' => $result['school']->id,
                'school_name' => $result['school']->name,
                'admin_name' => $result['admin']->name,
                'admin_email' => $result['admin']->email,
                'temporary_password' => $result['temporary_password'],
                'license_reference' => $result['license']->payment_reference,
                'license_amount' => $result['license']->amount,
                'payment_reference' => $result['payment']->reference,
                'payment_amount' => $result['payment']->amount,
            ])
            ->with('success', 'École « '.$demoRequest->school_name
                .' » activée. Licence initiale toute la première année, régime 150 000 FCFA.');
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(DemoRequest $demo): array
    {
        return [
            'id' => $demo->id,
            'school_name' => $demo->school_name,
            'address' => $demo->address,
            'phone' => $demo->phone,
            'email' => $demo->email,
            'preferred_demo_date' => $demo->preferred_demo_date?->format('d/m/Y'),
            'preferred_demo_time' => $demo->preferred_demo_time,
            'preferred_slot' => $demo->preferredSlot(),
            'status' => $demo->status->value,
            'status_label' => $demo->status->label(),
            'handler' => $demo->handler
                ? ['id' => $demo->handler->id, 'name' => $demo->handler->name]
                : null,
            'created_at' => $demo->created_at->format('d/m/Y H:i'),
        ];
    }
}
