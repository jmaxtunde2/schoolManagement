<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Teacher;
use App\Http\Controllers\Staff\EvaluationController as StaffEvaluationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public / Landing
|--------------------------------------------------------------------------
*/

Route::get('/', function (\Illuminate\Http\Request $request) {
    $user = auth()->user();

    if ($user) {
        return redirect()->route($user->role->homeRoute());
    }

    return app(\App\Http\Controllers\PublicSchoolController::class)
        ->__invoke(
            $request,
            app(\App\Services\Tenancy\TenantResolver::class)
        );
})->name('public.school.home');

Route::get('/ecole/{slug}', [
    \App\Http\Controllers\PublicSchoolController::class,
    'bySlug',
])->name('public.school.slug');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [
        AuthenticatedSessionController::class,
        'create',
    ])->name('login');

    Route::post('/login', [
        AuthenticatedSessionController::class,
        'store',
    ])->middleware('throttle:20,1');

    /*
    |--------------------------------------------------------------------------
    | Google Authentication
    |--------------------------------------------------------------------------
    */

    Route::get('/auth/google', [
        GoogleAuthController::class,
        'redirect',
    ])->name('google.redirect');
});

Route::get('/auth/google/callback', [
    GoogleAuthController::class,
    'callback',
])->name('google.callback');

Route::get('/auth/google/reauth', [
    GoogleAuthController::class,
    'redirect',
])
    ->middleware('auth')
    ->name('google.reauth.start');

/*
|--------------------------------------------------------------------------
| Two-Factor Authentication
|--------------------------------------------------------------------------
|
| Authenticator / TOTP
|
*/

Route::middleware('auth')
    ->prefix('two-factor')
    ->name('two-factor.')
    ->group(function () {

        // Configuration initiale
        Route::get('/setup', [
            \App\Http\Controllers\Auth\TwoFactorController::class,
            'setup',
        ])->name('setup');

        Route::post('/setup', [
            \App\Http\Controllers\Auth\TwoFactorController::class,
            'confirm',
        ])->name('setup.confirm');

        // Codes de récupération
        Route::get('/recovery', [
            \App\Http\Controllers\Auth\TwoFactorController::class,
            'recovery',
        ])->name('recovery');

        // Vérification Authenticator
        Route::get('/challenge', [
            \App\Http\Controllers\Auth\TwoFactorController::class,
            'challenge',
        ])->name('challenge');

        Route::post('/challenge', [
            \App\Http\Controllers\Auth\TwoFactorController::class,
            'verify',
        ])->name('verify');

        Route::post('/challenge/cancel', [
            \App\Http\Controllers\Auth\TwoFactorController::class,
            'cancel',
        ])->name('cancel');
    });

/*
|--------------------------------------------------------------------------
| Authenticated Application
|--------------------------------------------------------------------------
|
| Toutes les routes ci-dessous nécessitent :
| - authentification
| - tenant / école valide
| - 2FA configurée
| - 2FA vérifiée
|
*/

Route::middleware([
    'auth',
    'school',
    '2fa.configured',
    '2fa.verified',
])->group(function () {

    Route::get('/notifications', [
        \App\Http\Controllers\UserNotificationController::class,
        'index',
    ])->name('notifications.index');

    Route::post('/notifications/{notification}/read', [
        \App\Http\Controllers\UserNotificationController::class,
        'markAsRead',
    ])->name('notifications.read');

    Route::post('/notifications/read-all', [
        \App\Http\Controllers\UserNotificationController::class,
        'markAllAsRead',
    ])->name('notifications.read-all');

    Route::post('/logout', [
        AuthenticatedSessionController::class,
        'destroy',
    ])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Platform Administration
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:platform_admin')
        ->prefix('platform')
        ->name('platform.')
        ->group(function () {

            Route::get('/dashboard', 
                \App\Http\Controllers\Platform\DashboardController::class
            )->name('dashboard');
        });

    /*
    |--------------------------------------------------------------------------
    | School Administration
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            /*
            | Dashboard
            */

            Route::get('/dashboard', Admin\DashboardController::class)
                ->name('dashboard');

            /*
            | School Settings
            */

            Route::get('/settings', [
                Admin\SchoolSettingsController::class,
                'edit',
            ])->name('settings.edit');

            Route::put('/settings', [
                Admin\SchoolSettingsController::class,
                'update',
            ])->name('settings.update');

            Route::get('/settings/public-site', [
                Admin\PublicSiteController::class,
                'edit',
            ])->name('settings.public-site.edit');

            Route::put('/settings/public-site', [
                Admin\PublicSiteController::class,
                'update',
            ])->name('settings.public-site.update');

            Route::post('/settings/public-site/testimonials', [
                Admin\PublicSiteController::class,
                'storeTestimonial',
            ])->name('settings.public-site.testimonials.store');

            Route::put('/settings/public-site/testimonials/{testimonial}', [
                Admin\PublicSiteController::class,
                'updateTestimonial',
            ])->name('settings.public-site.testimonials.update');

            Route::delete('/settings/public-site/testimonials/{testimonial}', [
                Admin\PublicSiteController::class,
                'destroyTestimonial',
            ])->name('settings.public-site.testimonials.destroy');

            Route::post('/settings/public-site/gallery', [
                Admin\PublicSiteController::class,
                'storeGalleryItem',
            ])->name('settings.public-site.gallery.store');

            Route::put('/settings/public-site/gallery/{galleryItem}', [
                Admin\PublicSiteController::class,
                'updateGalleryItem',
            ])->name('settings.public-site.gallery.update');

            Route::delete('/settings/public-site/gallery/{galleryItem}', [
                Admin\PublicSiteController::class,
                'destroyGalleryItem',
            ])->name('settings.public-site.gallery.destroy');

            Route::get('/settings/email', [
                \App\Http\Controllers\Admin\EmailSettingsController::class,
                'edit',
            ])->name('settings.email');

            Route::put('/settings/email', [
                \App\Http\Controllers\Admin\EmailSettingsController::class,
                'update',
            ])->name('settings.email.update');

            Route::post('/settings/email/test', [
                \App\Http\Controllers\Admin\EmailSettingsController::class,
                'sendTestEmail',
            ])->name('settings.email.test');

            /*
            | Billing
            */

            Route::get('/billing', [
                Admin\BillingController::class,
                'index',
            ])->name('billing.index');

            /*
            | Users
            */

            Route::get('/users', [
                Admin\UserController::class,
                'index',
            ])->name('users.index');

            Route::post('/users', [
                Admin\UserController::class,
                'store',
            ])
                ->middleware('2fa.fresh:manage_users')
                ->name('users.store');

            Route::put('/users/{user}', [
                Admin\UserController::class,
                'update',
            ])
                ->middleware('2fa.fresh:manage_users')
                ->name('users.update');

            /*
            | Academic Years
            */

            Route::get('/academic-years', [
                Admin\AcademicYearController::class,
                'index',
            ])->name('academic-years.index');

            Route::post('/academic-years', [
                Admin\AcademicYearController::class,
                'store',
            ])->name('academic-years.store');

            Route::put('/academic-years/{academic_year}', [
                Admin\AcademicYearController::class,
                'update',
            ])->name('academic-years.update');

            Route::delete('/academic-years/{academic_year}', [
                Admin\AcademicYearController::class,
                'destroy',
            ])->name('academic-years.destroy');

            /*
            | Classes
            */

            Route::get('/classes', [
                Admin\ClassRoomController::class,
                'index',
            ])->name('classes.index');

            Route::post('/classes', [
                Admin\ClassRoomController::class,
                'store',
            ])->name('classes.store');

            Route::put('/classes/{class}', [
                Admin\ClassRoomController::class,
                'update',
            ])->name('classes.update');

            Route::delete('/classes/{class}', [
                Admin\ClassRoomController::class,
                'destroy',
            ])->name('classes.destroy');

            /*
            | Subjects
            */

            Route::get('/subjects', [
                Admin\SubjectController::class,
                'index',
            ])->name('subjects.index');

            Route::post('/subjects', [
                Admin\SubjectController::class,
                'store',
            ])->name('subjects.store');

            Route::put('/subjects/{subject}', [
                Admin\SubjectController::class,
                'update',
            ])->name('subjects.update');

            Route::delete('/subjects/{subject}', [
                Admin\SubjectController::class,
                'destroy',
            ])->name('subjects.destroy');

            /*
            | Teachers
            */

            Route::get('/teachers', [
                Admin\TeacherController::class,
                'index',
            ])->name('teachers.index');

            Route::post('/teachers', [
                Admin\TeacherController::class,
                'store',
            ])->name('teachers.store');

            Route::put('/teachers/{teacher}', [
                Admin\TeacherController::class,
                'update',
            ])->name('teachers.update');

            Route::delete('/teachers/{teacher}', [
                Admin\TeacherController::class,
                'destroy',
            ])->name('teachers.destroy');

            /*
            | Guardians / Parents
            */

            Route::get('/guardians', [
                Admin\GuardianController::class,
                'index',
            ])->name('guardians.index');

            Route::post('/guardians', [
                Admin\GuardianController::class,
                'store',
            ])->name('guardians.store');

            Route::put('/guardians/{guardian}', [
                Admin\GuardianController::class,
                'update',
            ])->name('guardians.update');

            Route::delete('/guardians/{guardian}', [
                Admin\GuardianController::class,
                'destroy',
            ])->name('guardians.destroy');

            /*
            | Students
            */

            Route::get('/students', [
                Admin\StudentController::class,
                'index',
            ])->name('students.index');

            Route::post('/students', [
                Admin\StudentController::class,
                'store',
            ])->name('students.store');

            Route::put('/students/{student}', [
                Admin\StudentController::class,
                'update',
            ])->name('students.update');

            Route::delete('/students/{student}', [
                Admin\StudentController::class,
                'destroy',
            ])->name('students.destroy');

            /*
            | Evaluations
            */

            Route::get('/evaluations', [
                Admin\EvaluationController::class,
                'index',
            ])->name('evaluations.index');

            Route::get('/evaluations/{evaluation}/grades', [
                StaffEvaluationController::class,
                'grades',
            ])->name('evaluations.grades');

            Route::post('/evaluations/{evaluation}/validate', [
                Admin\EvaluationValidationController::class,
                'store',
            ])
                ->middleware('2fa.fresh:validate_evaluation')
                ->name('evaluations.validate');

            Route::post('/evaluations/{evaluation}/return', [
                Admin\EvaluationReturnController::class,
                'store',
            ])->name('evaluations.return');

            Route::get('/academic/results', [
                Admin\AcademicResultController::class,
                'index',
            ])->name('academic.results.index');

            Route::post('/academic/report-cards/generate', [
                Admin\AcademicResultController::class,
                'generate',
            ])->name('academic.report-cards.generate');

            Route::get('/report-cards', [
                Admin\ReportCardController::class,
                'index',
            ])->name('report-cards.index');

            Route::get('/report-cards/{reportCard}', [
                Admin\ReportCardController::class,
                'show',
            ])->name('report-cards.show');

            Route::put('/report-cards/{reportCard}/comments', [
                Admin\ReportCardController::class,
                'updateComments',
            ])->name('report-cards.comments.update');

            Route::post('/report-cards/{reportCard}/publish', [
                Admin\ReportCardController::class,
                'publish',
            ])->middleware('2fa.fresh:publish_report_card')
                ->name('report-cards.publish');

            Route::post('/report-cards/{reportCard}/regenerate', [
                Admin\ReportCardController::class,
                'regenerate',
            ])->middleware('2fa.fresh:regenerate_report_card')
                ->name('report-cards.regenerate');

            Route::get('/report-cards/{reportCard}/pdf', [
                Admin\ReportCardController::class,
                'download',
            ])->name('report-cards.pdf');

            Route::get('/attendance', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'index',
            ])->name('attendance.index');

            Route::get('/attendance/create', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'create',
            ])->name('attendance.create');

            Route::post('/attendance', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'store',
            ])->name('attendance.store');

            Route::get('/attendance/students/{student}', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'studentHistory',
            ])->name('attendance.students.show');

            Route::put('/attendance/{attendance}', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'update',
            ])->name('attendance.update');

            Route::post('/attendance/{attendance}/justify', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'reviewJustification',
            ])->name('attendance.justify');

            /*
            | Notifications
            */

            Route::get('/notifications', [
                Admin\NotificationController::class,
                'index',
            ])->name('notifications.index');

            Route::post('/notifications/{notification}/resend', [
                Admin\NotificationController::class,
                'resend',
            ])->name('notifications.resend');
        });

    /*
    |--------------------------------------------------------------------------
    | Censeur
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:censeur')
        ->prefix('censeur')
        ->name('censeur.')
        ->group(function () {

            Route::get('/dashboard',
                \App\Http\Controllers\Censeur\DashboardController::class
            )->name('dashboard');

            Route::get('/evaluations', [
                \App\Http\Controllers\Censeur\EvaluationController::class,
                'index',
            ])->name('evaluations.index');

            Route::get('/evaluations/{evaluation}/grades', [
                StaffEvaluationController::class,
                'grades',
            ])->name('evaluations.grades');

            Route::get('/academic/results', [
                Admin\AcademicResultController::class,
                'index',
            ])->name('academic.results.index');

            Route::post('/academic/report-cards/generate', [
                Admin\AcademicResultController::class,
                'generate',
            ])->name('academic.report-cards.generate');

            Route::get('/report-cards', [
                Admin\ReportCardController::class,
                'index',
            ])->name('report-cards.index');

            Route::get('/report-cards/{reportCard}', [
                Admin\ReportCardController::class,
                'show',
            ])->name('report-cards.show');

            Route::put('/report-cards/{reportCard}/comments', [
                Admin\ReportCardController::class,
                'updateComments',
            ])->name('report-cards.comments.update');

            Route::post('/report-cards/{reportCard}/publish', [
                Admin\ReportCardController::class,
                'publish',
            ])->middleware('2fa.fresh:publish_report_card')
                ->name('report-cards.publish');

            Route::post('/report-cards/{reportCard}/regenerate', [
                Admin\ReportCardController::class,
                'regenerate',
            ])->middleware('2fa.fresh:regenerate_report_card')
                ->name('report-cards.regenerate');

            Route::get('/report-cards/{reportCard}/pdf', [
                Admin\ReportCardController::class,
                'download',
            ])->name('report-cards.pdf');

            Route::get('/attendance', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'index',
            ])->name('attendance.index');

            Route::get('/attendance/create', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'create',
            ])->name('attendance.create');

            Route::post('/attendance', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'store',
            ])->name('attendance.store');

            Route::get('/attendance/students/{student}', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'studentHistory',
            ])->name('attendance.students.show');

            Route::put('/attendance/{attendance}', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'update',
            ])->name('attendance.update');

            Route::post('/attendance/{attendance}/justify', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'reviewJustification',
            ])->name('attendance.justify');

            Route::post('/evaluations/{evaluation}/validate', [
                Admin\EvaluationValidationController::class,
                'store',
            ])
                ->middleware('2fa.fresh:validate_evaluation')
                ->name('evaluations.validate');

            Route::post('/evaluations/{evaluation}/return', [
                Admin\EvaluationReturnController::class,
                'store',
            ])->name('evaluations.return');

            Route::get('/classes', [
                Admin\ClassRoomController::class,
                'index',
            ])->name('classes.index');

            Route::post('/classes', [
                Admin\ClassRoomController::class,
                'store',
            ])->name('classes.store');

            Route::get('/subjects', [
                Admin\SubjectController::class,
                'index',
            ])->name('subjects.index');

            Route::post('/subjects', [
                Admin\SubjectController::class,
                'store',
            ])->name('subjects.store');

            Route::get('/teachers', [
                Admin\TeacherController::class,
                'index',
            ])->name('teachers.index');

            Route::get('/guardians', [
                Admin\GuardianController::class,
                'index',
            ])->name('guardians.index');

            Route::get('/students', [
                Admin\StudentController::class,
                'index',
            ])->name('students.index');
        });

    /*
    |--------------------------------------------------------------------------
    | Teacher / Secretary — Evaluation Workflow
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:teacher,secretary')
        ->prefix('teacher')
        ->name('teacher.')
        ->group(function () {

            /*
            | Teacher Dashboard
            */

            Route::get('/dashboard',
                Teacher\DashboardController::class
            )
                ->middleware('role:teacher')
                ->name('dashboard');

            /*
            | Evaluations
            */

            Route::get('/evaluations', [
                StaffEvaluationController::class,
                'index',
            ])->name('evaluations.index');

            Route::get('/academic/results', [
                Admin\AcademicResultController::class,
                'index',
            ])->name('academic.results.index');

            Route::get('/report-cards', [
                Admin\ReportCardController::class,
                'index',
            ])->name('report-cards.index');

            Route::get('/report-cards/{reportCard}', [
                Admin\ReportCardController::class,
                'show',
            ])->name('report-cards.show');

            Route::put('/report-cards/{reportCard}/comments', [
                Admin\ReportCardController::class,
                'updateComments',
            ])->name('report-cards.comments.update');

            Route::post('/academic/report-cards/generate', [
                Admin\AcademicResultController::class,
                'generate',
            ])->name('academic.report-cards.generate');

            Route::get('/report-cards/{reportCard}/pdf', [
                Admin\ReportCardController::class,
                'download',
            ])->name('report-cards.pdf');

            Route::get('/attendance', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'index',
            ])->name('attendance.index');

            Route::get('/attendance/create', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'create',
            ])->name('attendance.create');

            Route::post('/attendance', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'store',
            ])->name('attendance.store');

            Route::get('/attendance/students/{student}', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'studentHistory',
            ])->name('attendance.students.show');

            Route::put('/attendance/{attendance}', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'update',
            ])->name('attendance.update');

            Route::post('/attendance/{attendance}/justify', [
                \App\Http\Controllers\Staff\AttendanceController::class,
                'reviewJustification',
            ])->name('attendance.justify');

            Route::get('/evaluations/create', [
                StaffEvaluationController::class,
                'create',
            ])->name('evaluations.create');

            Route::post('/evaluations', [
                StaffEvaluationController::class,
                'store',
            ])->name('evaluations.store');

            Route::get('/evaluations/{evaluation}/grades', [
                StaffEvaluationController::class,
                'grades',
            ])->name('evaluations.grades');

            Route::put('/evaluations/{evaluation}/grades', [
                StaffEvaluationController::class,
                'updateGrades',
            ])->name('evaluations.grades.update');

            Route::post('/evaluations/{evaluation}/submit', [
                StaffEvaluationController::class,
                'submit',
            ])
                ->middleware('2fa.fresh:submit_evaluation')
                ->name('evaluations.submit');
        });

    /*
    |--------------------------------------------------------------------------
    | Parent
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:parent')
        ->prefix('parent')
        ->name('parent.')
        ->group(function () {

            Route::get('/dashboard',
                \App\Http\Controllers\Parent\DashboardController::class
            )->name('dashboard');

            /*
            | Children
            */

            Route::get('/children/{student}', [
                \App\Http\Controllers\Parent\ChildController::class,
                'show',
            ])->name('children.show');

            /*
            | Attendance
            */

            Route::get('/children/{student}/attendance', [
                \App\Http\Controllers\Parent\ChildController::class,
                'attendance',
            ])->name('children.attendance');

            Route::get('/children/{student}/report-cards', [
                \App\Http\Controllers\Parent\ChildController::class,
                'reportCards',
            ])->name('children.report-cards');

            Route::get('/report-cards/{reportCard}', [
                Admin\ReportCardController::class,
                'show',
            ])->name('report-cards.show');

            Route::get('/report-cards/{reportCard}/pdf', [
                Admin\ReportCardController::class,
                'download',
            ])->name('report-cards.pdf');

            Route::post('/attendance/{attendance}/justification', [
                \App\Http\Controllers\Parent\ChildController::class,
                'justify',
            ])->name('attendance.justify');
        });

    /*
    |--------------------------------------------------------------------------
    | Secretary
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:secretary')
        ->prefix('secretary')
        ->name('secretary.')
        ->group(function () {

            Route::get('/dashboard',
                \App\Http\Controllers\Secretary\DashboardController::class
            )->name('dashboard');
        });
});