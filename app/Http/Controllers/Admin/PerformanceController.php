<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Performance\BuildPerformanceDashboard;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceController extends Controller
{
    public function __invoke(BuildPerformanceDashboard $dashboard): Response
    {
        return Inertia::render('Admin/Performance/Index', [
            'performance' => $dashboard->handle(),
        ]);
    }
}