<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentFollowUp;
use App\Services\Assistant\AssistantContext;
use App\Services\Students\StudentRiskAssessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Suivi des élèves en difficulté (phase 5).
 *
 * Accessible aux administrateurs (établissement entier) et aux directeurs
 * primaires (périmètre primaire). Les indicateurs produits sont scolaires et
 * ne constituent pas un diagnostic médical ou psychologique.
 */
class StudentFollowUpController extends Controller
{
    public function index(Request $request, StudentRiskAssessment $assessment): Response
    {
        $context = AssistantContext::for($request->user());
        $students = $assessment->handle($context);

        return Inertia::render('Admin/Students/AtRisk/Index', [
            'students' => $students,
            'thresholds' => config('alerts.difficulty'),
            'routePrefix' => $this->routePrefix($request),
            'scope_note' => $context->scopeNote(),
        ]);
    }

    public function show(Request $request, Student $student, StudentRiskAssessment $assessment): Response
    {
        $context = AssistantContext::for($request->user());
        $this->ensureInScope($context, $student);

        $assessmentEntry = $assessment->forStudent($context, $student);

        $followUps = StudentFollowUp::query()
            ->where('student_id', $student->id)
            ->with('author:id,name')
            ->latest('id')
            ->get()
            ->map(fn (StudentFollowUp $followUp) => [
                'id' => $followUp->id,
                'type' => $followUp->type->value,
                'type_label' => $followUp->type->label(),
                'title' => $followUp->title,
                'notes' => $followUp->notes,
                'status' => $followUp->status->value,
                'status_label' => $followUp->status->label(),
                'status_tone' => $followUp->status->tone(),
                'due_date' => $followUp->due_date?->toDateString(),
                'author' => $followUp->author?->name,
                'created_at' => $followUp->created_at?->toISOString(),
            ]);

        return Inertia::render('Admin/Students/AtRisk/Show', [
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'matricule' => $student->matricule,
                'class_name' => $student->classRoom?->name,
            ],
            'assessment' => $assessmentEntry,
            'followUps' => $followUps,
            'actions' => [
                'types' => FollowUpType::options(),
                'statuses' => FollowUpStatus::options(),
            ],
            'thresholds' => config('alerts.difficulty'),
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function store(Request $request, Student $student): RedirectResponse
    {
        $context = AssistantContext::for($request->user());
        $this->ensureInScope($context, $student);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(FollowUpType::class)],
            'title' => ['required', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(FollowUpStatus::class)],
            'due_date' => ['nullable', 'date'],
        ]);

        StudentFollowUp::create([
            'student_id' => $student->id,
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        return back()->with('success', 'Action de suivi enregistrée.');
    }

    private function ensureInScope(AssistantContext $context, Student $student): void
    {
        if ($context->allowedClassIds !== null && ! in_array((int) $student->class_id, $context->allowedClassIds, true)) {
            abort(403);
        }
    }

    private function routePrefix(Request $request): string
    {
        return $request->routeIs('director.*') ? 'director' : 'admin';
    }
}
