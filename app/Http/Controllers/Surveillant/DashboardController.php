<?php

namespace App\Http\Controllers\Surveillant;

use App\Actions\Attendance\BuildRecentIncidents;
use App\Actions\Students\BuildGenderDistribution;
use App\Actions\Timetable\BuildTimetableBrief;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\Student;
use App\Models\Teacher;
use Inertia\Inertia;

/**
 * Tableau de bord du surveillant.
 *
 * Le surveillant est centré sur la vie scolaire : assiduité (retards / absences)
 * et exécution de l'emploi du temps du jour. Il ne gère ni les évaluations ni les
 * paramètres de l'établissement.
 */
class DashboardController extends Controller
{
    public function __invoke(BuildTimetableBrief $brief, BuildRecentIncidents $incidents, BuildGenderDistribution $genderDistribution)
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
            'routePrefix' => 'surveillant',
            'finance' => false,
            'timetableBrief' => $brief->handle(),
            'recentIncidents' => $incidents->handle(10),
            'genderDistribution' => $genderDistribution->handle(),
        ]);
    }
}
