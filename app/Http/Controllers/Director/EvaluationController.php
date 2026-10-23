<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationController extends Controller
{
    public function index(Request $request): Response
    {
        $primaryClassIds = ClassRoom::primary()->pluck('id');

        $evaluations = Evaluation::with(['classRoom:id,name', 'subject:id,name', 'teacher.user:id,name'])
            ->whereIn('class_id', $primaryClassIds)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Evaluations/Index', [
            'evaluations' => $evaluations,
            'filters' => $request->only('status'),
            'routePrefix' => 'director',
        ]);
    }
}
