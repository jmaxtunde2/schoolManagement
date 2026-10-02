<?php
namespace App\Http\Controllers\Censeur;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Student;
use Inertia\Inertia;

class DashboardController extends Controller
{
	public function __invoke()
	{
		return Inertia::render('Admin/Dashboard', [
			'stats' => [
				'students' => Student::count(),
				'teachers' => \App\Models\Teacher::count(),
				'classes' => \App\Models\ClassRoom::count(),
				'evaluations' => Evaluation::count(),
				'notifications_sent' => \App\Models\Notification::where('status', 'sent')->count(),
				'notifications_failed' => \App\Models\Notification::where('status', 'failed')->count(),
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
