<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'students' => Student::count(),
                'teachers' => Teacher::count(),
                'classes' => ClassRoom::count(),
                'evaluations' => Evaluation::count(),
                'notifications_sent' => Notification::whereIn('status', ['sent', 'delivered'])->count(),
                'notifications_failed' => Notification::where('status', 'failed')->count(),
            ],
            'recentEvaluations' => Evaluation::with(['classRoom:id,name', 'subject:id,name', 'teacher.user:id,name'])
                ->latest('evaluation_date')->take(5)->get(),
        ]);
    }
}
