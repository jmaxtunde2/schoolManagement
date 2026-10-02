<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationController extends Controller
{
    public function index(Request $request): Response
    {
        $evaluations = Evaluation::with(['classRoom:id,name', 'subject:id,name', 'teacher.user:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('evaluation_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Evaluations/Index', [
            'evaluations' => $evaluations,
            'filters' => $request->only('status'),
            'routePrefix' => 'admin',
        ]);
    }
}
