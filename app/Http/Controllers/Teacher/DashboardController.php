<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $teacher = $user->teacher;

        if (! $teacher) {
            abort(
                403,
                'Votre compte enseignant n’est pas encore associé à un profil enseignant.'
            );
        }

        $teacher->load([
            'assignments.classRoom:id,name',
            'assignments.subject:id,name',
        ]);

        $evaluations = Evaluation::with([
            'classRoom:id,name',
            'subject:id,name',
        ])
            ->where('teacher_id', $teacher->id)
            ->latest('evaluation_date')
            ->take(5)
            ->get();

        return Inertia::render('Teacher/Dashboard', [
            'assignments' => $teacher->assignments->map(
                fn ($a) => [
                    'class_name' => $a->classRoom?->name,
                    'subject_name' => $a->subject?->name,
                ]
            ),

            'recentEvaluations' => $evaluations,

            'draftCount' => Evaluation::where(
                'teacher_id',
                $teacher->id
            )
                ->whereIn('status', [
                    'draft',
                    'in_progress',
                ])
                ->count(),
        ]);
    }
}
