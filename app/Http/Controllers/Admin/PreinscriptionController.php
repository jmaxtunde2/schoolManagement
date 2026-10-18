<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Preinscriptions\ApprovePreinscription;
use App\Enums\PreinscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Preinscription;
use App\Services\Assistant\AssistantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Revue des demandes de préinscription (phase 7).
 *
 * L'administration valide (création automatique de l'élève + affectation de
 * classe) ou refuse une demande. Le directeur primaire ne voit que les
 * demandes relevant du primaire.
 */
class PreinscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $context = AssistantContext::for($request->user());

        $preinscriptions = Preinscription::query()
            ->when(
                $context->allowedClassIds !== null,
                fn ($query) => $query->whereIn('requested_level', ClassRoom::PRIMARY_LEVELS),
            )
            ->orderByRaw("CASE WHEN status = '".PreinscriptionStatus::Pending->value."' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Preinscription $preinscription) => [
                'id' => $preinscription->id,
                'full_name' => $preinscription->fullName(),
                'first_name' => $preinscription->first_name,
                'last_name' => $preinscription->last_name,
                'birth_date' => optional($preinscription->birth_date)->format('d/m/Y'),
                'gender' => $preinscription->gender,
                'requested_level' => $preinscription->requested_level,
                'parent_name' => $preinscription->parent_name,
                'parent_phone' => $preinscription->parent_phone,
                'parent_email' => $preinscription->parent_email,
                'parent_relationship' => $preinscription->parent_relationship,
                'notes' => $preinscription->notes,
                'documents' => $preinscription->documentLinks(),
                'status' => [
                    'value' => $preinscription->status->value,
                    'label' => $preinscription->status->label(),
                    'tone' => $preinscription->status->tone(),
                ],
                'rejection_reason' => $preinscription->rejection_reason,
                'student_id' => $preinscription->student_id,
                'created_at' => optional($preinscription->created_at)->format('d/m/Y H:i'),
            ]);

        $classes = $context->classes()->map(fn (ClassRoom $class) => [
            'id' => $class->id,
            'name' => $class->name,
            'level' => $class->level,
        ])->values();

        return Inertia::render('Admin/Preinscriptions/Index', [
            'preinscriptions' => $preinscriptions,
            'classes' => $classes,
            'scope_note' => $context->scopeNote(),
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function approve(
        Request $request,
        Preinscription $preinscription,
        ApprovePreinscription $approve
    ): RedirectResponse {
        $context = AssistantContext::for($request->user());
        $this->ensureInScope($context, $preinscription);

        $data = $request->validate([
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->where('school_id', $request->user()->school_id),
            ],
        ]);

        abort_if($preinscription->status !== PreinscriptionStatus::Pending, 422);

        $classId = $data['class_id'] ?? null;

        if ($classId !== null && $context->allowedClassIds !== null && ! in_array((int) $classId, $context->allowedClassIds, true)) {
            abort(403);
        }

        $approve->handle($preinscription, $request->user(), $classId);

        return back()->with('success', 'Préinscription validée : l’élève a été créé.');
    }

    public function reject(Request $request, Preinscription $preinscription): RedirectResponse
    {
        $context = AssistantContext::for($request->user());
        $this->ensureInScope($context, $preinscription);

        abort_if($preinscription->status !== PreinscriptionStatus::Pending, 422);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $preinscription->update([
            'status' => PreinscriptionStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $data['reason'] ?? null,
        ]);

        return back()->with('success', 'Préinscription refusée.');
    }

    private function ensureInScope(AssistantContext $context, Preinscription $preinscription): void
    {
        if ($context->allowedClassIds === null) {
            return;
        }

        if (! in_array((string) $preinscription->requested_level, ClassRoom::PRIMARY_LEVELS, true)) {
            abort(403);
        }
    }

    private function routePrefix(Request $request): string
    {
        return $request->routeIs('director.*') ? 'director' : 'admin';
    }
}
