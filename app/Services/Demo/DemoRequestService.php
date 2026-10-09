<?php

namespace App\Services\Demo;

use App\Enums\DemoRequestStatus;
use App\Models\DemoRequest;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Workflow commercial d'une demande de démonstration.
 *
 *   pending ─→ contacted ─→ scheduled ─→ demo_done ─→ approved
 *      │                      │
 *      └─→ rejected           └─→ cancelled
 *
 * Chaque transition horodate l'étape correspondante, attribue la demande à
 * l'agent qui l'a traitée et laisse une trace dans `audit_logs` : le Super
 * Admin doit savoir qui a fait quoi, et quand.
 */
class DemoRequestService
{
    /** @var array<string, array<int, DemoRequestStatus>> */
    public const TRANSITIONS = [
        'pending' => ['contacted', 'rejected', 'cancelled'],
        'contacted' => ['scheduled', 'rejected', 'cancelled'],
        'scheduled' => ['demo_done', 'cancelled', 'rejected'],
        'demo_done' => ['approved', 'rejected'],
        'approved' => [],
        'rejected' => [],
        'cancelled' => [],
    ];

    public function __construct(private readonly AuditService $audit) {}

    /** @return array<int, DemoRequestStatus> */
    public static function allowedTransitions(DemoRequestStatus $from): array
    {
        return array_map(
            DemoRequestStatus::from(...),
            self::TRANSITIONS[$from->value] ?? []
        );
    }

    public function canTransition(DemoRequestStatus $from, DemoRequestStatus $to): bool
    {
        return in_array($to, self::allowedTransitions($from), true);
    }

    public function transition(DemoRequest $request, DemoRequestStatus $to, ?int $handledBy = null, ?string $note = null): DemoRequest
    {
        $from = $request->status;

        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(
                "Transition impossible : {$from->value} → {$to->value}."
            );
        }

        $timestamp = match ($to) {
            DemoRequestStatus::Contacted => 'contacted_at',
            DemoRequestStatus::Scheduled => 'scheduled_at',
            DemoRequestStatus::DemoDone => 'demo_done_at',
            DemoRequestStatus::Approved => 'approved_at',
            DemoRequestStatus::Rejected => 'rejected_at',
            DemoRequestStatus::Cancelled => 'cancelled_at',
        };

        DB::transaction(function () use ($request, $to, $handledBy, $note, $timestamp, $from) {
            $attributes = [
                'status' => $to,
                'handled_by' => $handledBy ?? auth()->id(),
            ];

            if ($timestamp !== null) {
                $attributes[$timestamp] = now();
            }

            if ($note !== null && $note !== '') {
                $attributes['internal_notes'] = trim(
                    trim((string) $request->internal_notes)."\n\n---\n".$note
                );
            }

            $request->update($attributes);

            $this->audit->log("demo_request.{$to->value}", $request, [
                'from' => $from->value,
                'to' => $to->value,
                'note' => $note,
            ]);
        });

        return $request->refresh();
    }
}
