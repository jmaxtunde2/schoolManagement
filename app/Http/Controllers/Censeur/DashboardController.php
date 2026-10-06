<?php

namespace App\Http\Controllers\Censeur;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\Student;
use App\Models\Teacher;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'students' => Student::count(),
                'teachers' => Teacher::count(),
                'classes' => ClassRoom::count(),
                'evaluations' => Evaluation::count(),
                'notifications_sent' => Notification::where('status', 'sent')->count(),
                'notifications_failed' => Notification::where('status', 'failed')->count(),
            ],
            'recentEvaluations' => Evaluation::with([
                'classRoom:id,name',
                'subject:id,name',
                'teacher.user:id,name',
            ])->latest('evaluation_date')->limit(8)->get(),
            'routePrefix' => 'censeur',
        ]);
    }
}
