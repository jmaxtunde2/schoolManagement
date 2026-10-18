<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Performance\BuildAcademicPerformance;
use App\Actions\Performance\BuildPerformanceDashboard;
use App\Http\Controllers\Controller;
use App\Services\Assistant\AssistantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceController extends Controller
{
    public function __invoke(Request $request, BuildPerformanceDashboard $dashboard, BuildAcademicPerformance $academic): Response
    {
        return Inertia::render('Admin/Performance/Index', [
            'performance' => $dashboard->handle(),
            'academic' => $academic->handle(AssistantContext::for($request->user())),
        ]);
    }
}
