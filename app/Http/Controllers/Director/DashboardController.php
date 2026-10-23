<?php

namespace App\Http\Controllers\Director;

use App\Actions\Attendance\BuildRecentIncidents;
use App\Actions\Pilotage\BuildPilotageDashboard;
use App\Actions\Students\BuildGenderDistribution;
use App\Actions\Timetable\BuildTimetableBrief;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Assistant\AssistantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildTimetableBrief $brief, BuildRecentIncidents $incidents, BuildGenderDistribution $genderDistribution, BuildPilotageDashboard $pilotage)
    {
        $primaryClassIds = ClassRoom::primary()->pluck('id');

        return Inertia::render('Admin/Dashboard', [
            'pilotage' => $pilotage->handle(AssistantContext::for($request->user()), 'director'),
            'stats' => [
                'students' => Student::whereIn('class_id', $primaryClassIds)->count(),
                'teachers' => Teacher::whereHas('assignments.classRoom', fn ($q) => $q->primary())->count(),
                'classes' => ClassRoom::primary()->count(),
                'evaluations' => Evaluation::whereIn('class_id', $primaryClassIds)->count(),
                'notifications_sent' => Notification::where('status', 'sent')->count(),
                'notifications_failed' => Notification::where('status', 'failed')->count(),
            ],
            'recentEvaluations' => Evaluation::with([
                'classRoom:id,name',
                'subject:id,name',
                'teacher.user:id,name',
            ])->whereIn('class_id', $primaryClassIds)
                ->latest('created_at')
                ->limit(8)
                ->get(),
            'routePrefix' => 'director',
            'finance' => false,
            'timetableBrief' => $brief->handle(null, $primaryClassIds),
            'recentIncidents' => $incidents->handle(10, $primaryClassIds),
            'genderDistribution' => $genderDistribution->handle($primaryClassIds),
        ]);
    }
}
