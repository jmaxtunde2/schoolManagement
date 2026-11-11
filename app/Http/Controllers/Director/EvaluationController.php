<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationController extends Controller
{
    public function index(Request $request): Response
    {
        $primaryClassIds = ClassRoom::primary()->pluck('id');

        $evaluations = Evaluation::with(['classRoom:id,name', 'subject:id,name', 'academicPeriod:id,name', 'teacher.user:id,name'])
            ->whereIn('class_id', $primaryClassIds)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', $request->integer('class_id')))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->integer('subject_id')))
            ->when($request->filled('academic_period_id'), fn ($q) => $q->where('academic_period_id', $request->integer('academic_period_id')))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Evaluations/Index', [
            'evaluations' => $evaluations,
            'filters' => $request->only('status', 'class_id', 'subject_id', 'academic_period_id'),
            'classes' => ClassRoom::query()->whereIn('id', $primaryClassIds)->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'periods' => AcademicPeriod::query()->orderBy('position')->get(['id', 'name']),
            'routePrefix' => 'director',
        ]);
    }
}
