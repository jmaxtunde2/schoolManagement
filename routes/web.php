<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\EmailSettingsController;
use App\Http\Controllers\Assistant\AssistantController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Censeur\EvaluationController;
use App\Http\Controllers\Communication;
use App\Http\Controllers\CoriSchoolLandingController;
use App\Http\Controllers\DemoRequestController;
use App\Http\Controllers\Director\DashboardController as DirectorDashboardController;
use App\Http\Controllers\Director\EvaluationController as DirectorEvaluationController;
use App\Http\Controllers\ExamBankController;
use App\Http\Controllers\Finance;
use App\Http\Controllers\Parent\ChildController;
use App\Http\Controllers\Parent\FinanceController;
use App\Http\Controllers\Parent\MessageController;
use App\Http\Controllers\Parent\NotificationSettingsController;
use App\Http\Controllers\Parent\TimetableController;
use App\Http\Controllers\Platform\AcademicPeriodController;
use App\Http\Controllers\Platform\AcademicYearController;
use App\Http\Controllers\Platform\AuditLogController;
use App\Http\Controllers\Platform\CommunicationController as PlatformCommunicationController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\DemoRequestController as PlatformDemoRequestController;
use App\Http\Controllers\Platform\ExamBankController as PlatformExamBankController;
use App\Http\Controllers\Platform\ImportController;
use App\Http\Controllers\Platform\LicenseController;
use App\Http\Controllers\Platform\SchoolController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPreinscriptionController;
use App\Http\Controllers\PublicSchoolController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Staff\AttendanceController;
use App\Http\Controllers\Staff\EvaluationController as StaffEvaluationController;
use App\Http\Controllers\Staff\ManagementDashboardController;
use App\Http\Controllers\Staff\ParentMessageController as StaffParentMessageController;
use App\Http\Controllers\Teacher;
use App\Http\Controllers\UserNotificationController;
use App\Services\Tenancy\TenantResolver;
use App\Support\ReservedSchoolSlugs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public / Landing
|--------------------------------------------------------------------------
*/

Route::get('/', function (Request $request) {
    $user = auth()->user();
    $preview = $request->boolean('preview');

    $resolver = app(TenantResolver::class);
    $isPlatformHost = ! $resolver->hasSchoolDomain($request->getHost());

    // Sur l'hôte de la plateforme : toujours afficher la landing CoriSchool
    // (même pour les utilisateurs connectés)
    if ($isPlatformHost) {
        return app(CoriSchoolLandingController::class)();
    }

    // Sur un domaine d'école : rediriger les utilisateurs connectés vers leur dashboard
    // sauf s'ils demandent explicitement l'aperçu (preview)
    if ($user && ! $preview) {
        return redirect()->route($user->role->homeRoute());
    }

    return app(PublicSchoolController::class)
        ->__invoke($request, $resolver);
})->name('public.school.home');

Route::get('/ecoles', [
    PublicSchoolController::class,
    'index',
])->name('public.schools.index');

Route::get('/ecole/{slug}', [
    PublicSchoolController::class,
    'bySlug',
])->name('public.school.slug.legacy');

/*
| Préinscription en ligne (phase 7) — formulaire public du mini-site.
| L'établissement est résolu depuis le slug, jamais depuis le formulaire.
*/

Route::get('/ecole/{slug}/preinscription', [
    PublicPreinscriptionController::class,
    'show',
])->name('public.school.preinscription');

Route::post('/ecole/{slug}/preinscription', [
    PublicPreinscriptionController::class,
    'store',
])->middleware('throttle:6,1')->name('public.school.preinscription.store');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

/*
| Landing pages for pedagogical resources
*/
Route::get('/banque-epreuves', [
    CoriSchoolLandingController::class,
    'banqueEpreuves',
])->name('public.banque-epreuves');

Route::get('/jeu-educatif', [
    CoriSchoolLandingController::class,
    'jeuEducatif',
])->name('public.jeu-educatif');

Route::get('/competitions', [
    CoriSchoolLandingController::class,
    'competitions',
])->name('public.competitions');

/*
|--------------------------------------------------------------------------
| Landing CoriSchool (plateforme) - accessible à tous
|--------------------------------------------------------------------------
*/
Route::get('/plateforme', [
    CoriSchoolLandingController::class,
    '__invoke',
])->name('public.platform.landing');

/*
|--------------------------------------------------------------------------
| Landing CoriSchool (plateforme)
|--------------------------------------------------------------------------
|
| `/` affiche le site public de l'établissement résolu lorsqu'un domaine est
| vérifié, sinon la landing CoriSchool (voir PublicSchoolController).
|
| Le parcours est public mais protégé par `guest` : un utilisateur déjà
| connecté n'a pas à redéposer une demande.
|
| Le formulaire public ne crée plus d'école : il dépose une demande de
| démonstration (voir DemoRequestController). L'activation ne survient
| qu'après démonstration, approbation et paiement initial confirmés.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/demander-une-demonstration', [
        DemoRequestController::class,
        'create',
    ])->name('demo.request');

    Route::post('/demander-une-demonstration', [
        DemoRequestController::class,
        'store',
    ])->middleware('throttle:10,1')->name('demo.request.store');

    // L'inscription directe n'est plus possible : les anciens liens
    // « Créer mon école » rejoignent le nouveau parcours commercial.
    Route::match(['get', 'post'], '/creer-mon-ecole', function () {
        return redirect()->route('demo.request');
    })->name('school.register');
});

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
            TwoFactorController::class,
            'setup',
        ])->name('setup');

        Route::post('/setup', [
            TwoFactorController::class,
            'confirm',
        ])->middleware('throttle:6,1')->name('setup.confirm');

        // Codes de récupération
        Route::get('/recovery', [
            TwoFactorController::class,
            'recovery',
        ])->name('recovery');

        // Vérification Authenticator
        Route::get('/challenge', [
            TwoFactorController::class,
            'challenge',
        ])->name('challenge');

        Route::post('/challenge', [
            TwoFactorController::class,
            'verify',
        ])->middleware('throttle:6,1')->name('verify');

        Route::post('/challenge/cancel', [
            TwoFactorController::class,
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
        UserNotificationController::class,
        'index',
    ])->name('notifications.index');

    Route::post('/notifications/{notification}/read', [
        UserNotificationController::class,
        'markAsRead',
    ])->name('notifications.read');

    Route::post('/notifications/read-all', [
        UserNotificationController::class,
        'markAllAsRead',
    ])->name('notifications.read-all');

    /*
    |--------------------------------------------------------------------------
    | Web Push (PWA)
    |--------------------------------------------------------------------------
    */

    Route::get('/push/vapid-key', [
        PushSubscriptionController::class,
        'vapidKey',
    ])->name('push.vapid-key');

    Route::post('/push/subscriptions', [
        PushSubscriptionController::class,
        'store',
    ])->name('push.subscriptions.store');

    Route::delete('/push/subscriptions', [
        PushSubscriptionController::class,
        'destroy',
    ])->name('push.subscriptions.destroy');

    Route::post('/logout', [
        AuthenticatedSessionController::class,
        'destroy',
    ])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Profil personnel
    |--------------------------------------------------------------------------
    |
    | Accessible à tous les rôles, y compris à l'administrateur Coriyase.
    | Le changement de mot de passe est une action sensible : il exige une
    | vérification 2FA récente, comme la gestion des utilisateurs.
    |
    */

    Route::get('/profile', [
        ProfileController::class,
        'edit',
    ])->name('profile.edit');

    Route::put('/profile', [
        ProfileController::class,
        'update',
    ])->name('profile.update');

    Route::put('/profile/password', [
        ProfileController::class,
        'updatePassword',
    ])
        ->middleware('2fa.fresh:change_password')
        ->name('profile.password.update');

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
                DashboardController::class
            )->name('dashboard');

            /*
            | Demandes de démonstration
            */

            Route::get('/demo-requests', [
                PlatformDemoRequestController::class,
                'index',
            ])->name('demo-requests.index');

            Route::get('/demo-requests/{demoRequest}', [
                PlatformDemoRequestController::class,
                'show',
            ])->name('demo-requests.show');

            Route::patch('/demo-requests/{demoRequest}/status', [
                PlatformDemoRequestController::class,
                'updateStatus',
            ])->name('demo-requests.status');

            Route::patch('/demo-requests/{demoRequest}/notes', [
                PlatformDemoRequestController::class,
                'updateNotes',
            ])->name('demo-requests.notes');

            /*
            | Approbation → activation
            |
            | Trois étapes protégées par une authentification fraîche
            | (code Authenticator) : demande de paiement initial, confirmation
            | manuelle du 150 000 FCFA, puis activation atomique de l'école
            | (School + compte administrateur).
            */

            Route::post('/demo-requests/{demoRequest}/payment/request', [
                PlatformDemoRequestController::class,
                'requestPayment',
            ])->middleware('2fa.fresh:request_payment')->name('demo-requests.payment.request');

            Route::post('/demo-requests/{demoRequest}/payment/confirm', [
                PlatformDemoRequestController::class,
                'confirmPayment',
            ])->middleware('2fa.fresh:confirm_payment')->name('demo-requests.payment.confirm');

            Route::post('/demo-requests/{demoRequest}/activate', [
                PlatformDemoRequestController::class,
                'activate',
            ])->middleware('2fa.fresh:activate_school')->name('demo-requests.activate');

            /*
            | Écoles
            */
            Route::get('/schools', [SchoolController::class, 'index'])->name('schools.index');
            Route::get('/schools/create', [SchoolController::class, 'create'])->name('schools.create');
            Route::post('/schools', [SchoolController::class, 'store'])->name('schools.store');
            Route::get('/schools/{school}', [SchoolController::class, 'show'])->name('schools.show');
            Route::post('/schools/{school}/toggle', [SchoolController::class, 'toggle'])
                ->middleware('2fa.fresh:toggle_school')->name('schools.toggle');
            Route::delete('/schools/{school}', [SchoolController::class, 'destroy'])
                ->middleware('2fa.fresh:delete_school')->name('schools.destroy');

            /*
            | Mini-site public (par école) — Admin plateforme
            */
            Route::get('/schools/{school}/public-site', [SchoolController::class, 'editPublicSite'])->name('schools.public-site.edit');
            Route::put('/schools/{school}/public-site', [SchoolController::class, 'updatePublicSite'])->name('schools.public-site.update');

            /*
            | Emploi du temps (par école) — Admin plateforme
            */
            Route::get('/schools/{school}/timetable', [SchoolController::class, 'showTimetable'])->name('schools.timetable.show');
            Route::post('/schools/{school}/timetable', [SchoolController::class, 'storeTimetableSlot'])->name('schools.timetable.store');
            Route::put('/schools/{school}/timetable/{timetable}', [SchoolController::class, 'updateTimetableSlot'])->name('schools.timetable.update');
            Route::delete('/schools/{school}/timetable/{timetable}', [SchoolController::class, 'destroyTimetableSlot'])->name('schools.timetable.destroy');

            /*
            | Licences
            */
            Route::get('/licenses', [LicenseController::class, 'index'])->name('licenses.index');
            Route::get('/licenses/{license}', [LicenseController::class, 'show'])->name('licenses.show');
            Route::post('/licenses/{license}/renew', [LicenseController::class, 'renew'])
                ->middleware('2fa.fresh:renew_license')->name('licenses.renew');

            /*
            | Journaux d'audit globaux
            */
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

            /*
            | Suivi de la communication (multi-établissements)
            */
            Route::get('/communication', [PlatformCommunicationController::class, 'index'])->name('communication.index');
            Route::get('/communication/history', [PlatformCommunicationController::class, 'history'])->name('communication.history');

            /*
            | Import en masse
            */
            Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
            Route::get('/imports/{school}', [ImportController::class, 'show'])->name('imports.show');
            Route::post('/imports/{school}', [ImportController::class, 'store'])->name('imports.store');
            Route::get('/imports/template/{type}', [ImportController::class, 'downloadTemplate'])->name('imports.template');

            /*
            | Années scolaires (par école)
            */
            Route::get('/schools/{school}/academic-years', [AcademicYearController::class, 'index'])->name('schools.academic-years.index');
            Route::post('/schools/{school}/academic-years', [AcademicYearController::class, 'store'])->name('schools.academic-years.store');
            Route::put('/schools/{school}/academic-years/{academicYear}', [AcademicYearController::class, 'update'])->name('schools.academic-years.update');
            Route::delete('/schools/{school}/academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])->name('schools.academic-years.destroy');
            Route::post('/schools/{school}/academic-years/{academicYear}/toggle', [AcademicYearController::class, 'toggle'])->name('schools.academic-years.toggle');

            /*
            | Périodes scolaires (par école)
            */
            Route::get('/schools/{school}/academic-periods', [AcademicPeriodController::class, 'index'])->name('schools.academic-periods.index');
            Route::post('/schools/{school}/academic-periods', [AcademicPeriodController::class, 'store'])->name('schools.academic-periods.store');
            Route::put('/schools/{school}/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'update'])->name('schools.academic-periods.update');
            Route::delete('/schools/{school}/academic-periods/{academicPeriod}', [AcademicPeriodController::class, 'destroy'])->name('schools.academic-periods.destroy');
            Route::post('/schools/{school}/academic-periods/{academicPeriod}/toggle', [AcademicPeriodController::class, 'toggle'])->name('schools.academic-periods.toggle');

            /*
            | Banque d'épreuves (banque centrale de la plateforme)
            */

            Route::get('/exam-bank', [PlatformExamBankController::class, 'index'])->name('exam-bank.index');
            Route::get('/exam-bank/references', [PlatformExamBankController::class, 'references'])->name('exam-bank.references');
            Route::get('/exam-bank/create', [PlatformExamBankController::class, 'create'])->name('exam-bank.create');
            Route::post('/exam-bank', [PlatformExamBankController::class, 'store'])->name('exam-bank.store');
            Route::post('/exam-bank/levels', [PlatformExamBankController::class, 'storeLevel'])->name('exam-bank.levels.store');
            Route::put('/exam-bank/levels/{platformLevel}', [PlatformExamBankController::class, 'updateLevel'])->name('exam-bank.levels.update');
            Route::delete('/exam-bank/levels/{platformLevel}', [PlatformExamBankController::class, 'destroyLevel'])->name('exam-bank.levels.destroy');
            Route::post('/exam-bank/subjects', [PlatformExamBankController::class, 'storeSubject'])->name('exam-bank.subjects.store');
            Route::put('/exam-bank/subjects/{platformSubject}', [PlatformExamBankController::class, 'updateSubject'])->name('exam-bank.subjects.update');
            Route::delete('/exam-bank/subjects/{platformSubject}', [PlatformExamBankController::class, 'destroySubject'])->name('exam-bank.subjects.destroy');
            Route::get('/exam-bank/{examResource}', [PlatformExamBankController::class, 'show'])->name('exam-bank.show');
            Route::get('/exam-bank/{examResource}/edit', [PlatformExamBankController::class, 'edit'])->name('exam-bank.edit');
            Route::put('/exam-bank/{examResource}', [PlatformExamBankController::class, 'update'])->name('exam-bank.update');
            Route::delete('/exam-bank/{examResource}', [PlatformExamBankController::class, 'destroy'])->name('exam-bank.destroy');
            Route::patch('/exam-bank/{examResource}/status', [PlatformExamBankController::class, 'updateStatus'])->name('exam-bank.status');
            Route::get('/exam-bank/{examResource}/attachments/{attachment}/download', [PlatformExamBankController::class, 'downloadAttachment'])->name('exam-bank.attachments.download');
            Route::delete('/exam-bank/{examResource}/attachments/{attachment}', [PlatformExamBankController::class, 'destroyAttachment'])->name('exam-bank.attachments.destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Export EducMaster (admin, censeur, directeur)
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin,censeur,director')
        ->prefix('educmaster')
        ->name('educmaster.')
        ->group(function () {
            Route::get('/', [
                Admin\EducMasterExportController::class,
                'index',
            ])->name('index');

            Route::get('/students', [
                Admin\EducMasterExportController::class,
                'students',
            ])->name('students');

            Route::get('/evaluation-notes', [
                Admin\EducMasterExportController::class,
                'notes',
            ])->name('notes');

            Route::get('/classes', [
                Admin\EducMasterExportController::class,
                'classes',
            ])->name('classes');

            Route::get('/timetable', [
                Admin\EducMasterExportController::class,
                'timetable',
            ])->name('timetable');
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
            | Banque d'épreuves (consultation)
            */

            Route::get('/banque-epreuves', [ExamBankController::class, 'index'])->name('exam-bank.index');
            Route::get('/banque-epreuves/{examResource}', [ExamBankController::class, 'show'])->name('exam-bank.show');
            Route::get('/banque-epreuves/{examResource}/attachments/{attachment}/download', [ExamBankController::class, 'download'])->name('exam-bank.download');
            Route::get('/banque-epreuves/{examResource}/attachments/{attachment}/answer-key', [ExamBankController::class, 'downloadAnswerKey'])->name('exam-bank.answer-key');

            /*
            | CoriSchool Assistant — pilotage en langage naturel
            */

            Route::get('/assistant', [AssistantController::class, 'index'])
                ->name('assistant.index');

            Route::post('/assistant/ask', [AssistantController::class, 'ask'])
                ->name('assistant.ask');

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
                EmailSettingsController::class,
                'edit',
            ])->name('settings.email');

            Route::put('/settings/email', [
                EmailSettingsController::class,
                'update',
            ])->name('settings.email.update');

            Route::post('/settings/email/test', [
                EmailSettingsController::class,
                'sendTestEmail',
            ])->name('settings.email.test');

            Route::get('/settings/communication', [
                Admin\CommunicationSettingsController::class,
                'edit',
            ])->name('settings.communication');

            Route::put('/settings/communication', [
                Admin\CommunicationSettingsController::class,
                'update',
            ])->name('settings.communication.update');

            /*
            | Billing
            */

            Route::get('/billing', [
                Admin\BillingController::class,
                'index',
            ])->name('billing.index');

            Route::get('/performance', Admin\PerformanceController::class)
                ->name('performance.index');

            /*
            | Finance — Dépenses, salaires, ventes, contributions
            */

            Route::get('/expenses', [
                Finance\ExpenseController::class,
                'index',
            ])->name('expenses.index');

            Route::post('/expenses', [
                Finance\ExpenseController::class,
                'store',
            ])->name('expenses.store');

            Route::put('/expenses/settings', [
                Finance\ExpenseController::class,
                'updateSettings',
            ])->name('expenses.settings');

            Route::post('/expenses/{expense}/approve', [
                Finance\ExpenseController::class,
                'approve',
            ])->name('expenses.approve');

            Route::post('/expenses/{expense}/reject', [
                Finance\ExpenseController::class,
                'reject',
            ])->name('expenses.reject');

            Route::delete('/expenses/{expense}', [
                Finance\ExpenseController::class,
                'destroy',
            ])->name('expenses.destroy');

            Route::get('/salaries', [
                Finance\SalaryController::class,
                'index',
            ])->name('salaries.index');

            Route::post('/salaries', [
                Finance\SalaryController::class,
                'save',
            ])->name('salaries.save');

            Route::get('/sales', [
                Finance\SaleController::class,
                'index',
            ])->name('sales.index');

            Route::post('/sales', [
                Finance\SaleController::class,
                'store',
            ])->name('sales.store');

            Route::delete('/sales/{sale}', [
                Finance\SaleController::class,
                'destroy',
            ])->name('sales.destroy');

            Route::get('/contributions', [
                Finance\ContributionController::class,
                'index',
            ])->name('contributions.index');

            Route::post('/contributions', [
                Finance\ContributionController::class,
                'store',
            ])->name('contributions.store');

            Route::delete('/contributions/{contribution}', [
                Finance\ContributionController::class,
                'destroy',
            ])->name('contributions.destroy');

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
            | Personnel
            |
            | Administration → Personnel : identité, rôle, téléphone, photo,
            | statut actif/inactif et identifiants de connexion.
            */

            Route::get('/personnel', [
                Admin\StaffController::class,
                'index',
            ])->name('staff.index');

            Route::post('/personnel', [
                Admin\StaffController::class,
                'store',
            ])
                ->middleware('2fa.fresh:manage_users')
                ->name('staff.store');

            Route::put('/personnel/{staff}', [
                Admin\StaffController::class,
                'update',
            ])
                ->middleware('2fa.fresh:manage_users')
                ->name('staff.update');

            Route::post('/personnel/{staff}/toggle', [
                Admin\StaffController::class,
                'toggle',
            ])->name('staff.toggle');

            Route::delete('/personnel/{staff}', [
                Admin\StaffController::class,
                'destroy',
            ])
                ->middleware('2fa.fresh:manage_users')
                ->name('staff.destroy');

            /*
            | Emploi du temps
            */

            Route::get('/timetables', [
                Admin\TimetableController::class,
                'index',
            ])->name('timetables.index');

            Route::post('/timetables', [
                Admin\TimetableController::class,
                'store',
            ])->name('timetables.store');

            Route::put('/timetables/{timetable}', [
                Admin\TimetableController::class,
                'update',
            ])->name('timetables.update');

            Route::delete('/timetables/{timetable}', [
                Admin\TimetableController::class,
                'destroy',
            ])->name('timetables.destroy');

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
            | Academic Periods
            */

            Route::get('/academic-periods', [
                Admin\AcademicPeriodController::class,
                'index',
            ])->name('academic-periods.index');

            Route::post('/academic-periods', [
                Admin\AcademicPeriodController::class,
                'store',
            ])->name('academic-periods.store');

            Route::put('/academic-periods/{academic_period}', [
                Admin\AcademicPeriodController::class,
                'update',
            ])->name('academic-periods.update');

            Route::delete('/academic-periods/{academic_period}', [
                Admin\AcademicPeriodController::class,
                'destroy',
            ])->name('academic-periods.destroy');

            Route::post('/academic-periods/{academic_period}/toggle', [
                Admin\AcademicPeriodController::class,
                'toggle',
            ])->name('academic-periods.toggle');

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
            | Rooms / Salles
            */

            Route::get('/rooms', [
                Admin\RoomController::class,
                'index',
            ])->name('rooms.index');

            Route::post('/rooms', [
                Admin\RoomController::class,
                'store',
            ])->name('rooms.store');

            Route::put('/rooms/{room}', [
                Admin\RoomController::class,
                'update',
            ])->name('rooms.update');

            Route::delete('/rooms/{room}', [
                Admin\RoomController::class,
                'destroy',
            ])->name('rooms.destroy');

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
            | Suivi des élèves en difficulté (phase 5)
            */

            Route::get('/students/at-risk', [
                Admin\StudentFollowUpController::class,
                'index',
            ])->name('students.at-risk');

            Route::get('/students/{student}/follow-up', [
                Admin\StudentFollowUpController::class,
                'show',
            ])->name('students.follow-up');

            Route::post('/students/{student}/follow-up', [
                Admin\StudentFollowUpController::class,
                'store',
            ])->name('students.follow-up.store');

            /*
            | Centre de documents (phase 6)
            */

            Route::get('/documents', [
                Admin\DocumentCenterController::class,
                'index',
            ])->name('documents.index');

            Route::get('/documents/statistics', [
                Admin\DocumentCenterController::class,
                'statistics',
            ])->name('documents.statistics');

            Route::get('/documents/finance', [
                Admin\DocumentCenterController::class,
                'finance',
            ])->name('documents.finance');

            Route::get('/documents/students', [
                Admin\DocumentCenterController::class,
                'studentsList',
            ])->name('documents.students');

            Route::get('/documents/classes', [
                Admin\DocumentCenterController::class,
                'roomList',
            ])->name('documents.classes');

            Route::get('/documents/classes/{classRoom}', [
                Admin\DocumentCenterController::class,
                'classList',
            ])->name('documents.class-list');

            Route::get('/documents/students/{student}/certificate', [
                Admin\DocumentCenterController::class,
                'certificate',
            ])->name('documents.certificate');

            Route::get('/documents/students/{student}/attestation', [
                Admin\DocumentCenterController::class,
                'attestation',
            ])->name('documents.attestation');

            /*
            | Préinscriptions (phase 7)
            */

            Route::get('/preinscriptions', [
                Admin\PreinscriptionController::class,
                'index',
            ])->name('preinscriptions.index');

            Route::post('/preinscriptions/{preinscription}/approve', [
                Admin\PreinscriptionController::class,
                'approve',
            ])->name('preinscriptions.approve');

            Route::post('/preinscriptions/{preinscription}/reject', [
                Admin\PreinscriptionController::class,
                'reject',
            ])->name('preinscriptions.reject');

            /*
            | Journal d'activité (phase 9)
            */

            Route::get('/audit-logs', [
                Admin\AuditLogController::class,
                'index',
            ])->name('audit-logs.index');

            Route::get('/audit-logs/{auditLog}', [
                Admin\AuditLogController::class,
                'show',
            ])->name('audit-logs.show');

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

            Route::post('/academic/results/export-educmaster', [
                Admin\AcademicResultController::class,
                'exportEducMaster',
            ])->name('academic.results.export-educmaster');

            Route::get('/academic/results/prepare-educmaster', [
                Admin\AcademicResultController::class,
                'prepareEducMaster',
            ])->name('academic.results.prepare-educmaster');

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
                AttendanceController::class,
                'index',
            ])->name('attendance.index');

            Route::get('/attendance/create', [
                AttendanceController::class,
                'create',
            ])->name('attendance.create');

            Route::post('/attendance', [
                AttendanceController::class,
                'store',
            ])->name('attendance.store');

            Route::get('/attendance/students/{student}', [
                AttendanceController::class,
                'studentHistory',
            ])->name('attendance.students.show');

            Route::put('/attendance/{attendance}', [
                AttendanceController::class,
                'update',
            ])->name('attendance.update');

            Route::post('/attendance/{attendance}/justify', [
                AttendanceController::class,
                'reviewJustification',
            ])->name('attendance.justify');

            Route::post('/attendance/validate', [
                AttendanceController::class,
                'validateRecords',
            ])->name('attendance.validate');

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
    | Communication — Annonces aux parents
    |--------------------------------------------------------------------------
    |
    | Annonces libres (réunion, information, convocation…) envoyées aux parents
    | par WhatsApp et/ou email. Accessible à l'administrateur, au censeur et à
    | la secrétaire. Ciblage : tous les parents, par classe, par niveau ou par
    | sélection de parents. Envoi immédiat ou programmé.
    */

    Route::middleware('role:admin,censeur,secretary')
        ->prefix('communication')
        ->name('communication.')
        ->group(function () {
            Route::get('/annonces', [
                Communication\AnnouncementController::class,
                'index',
            ])->name('announcements.index');

            Route::get('/annonces/nouvelle', [
                Communication\AnnouncementController::class,
                'create',
            ])->name('announcements.create');

            Route::post('/annonces', [
                Communication\AnnouncementController::class,
                'store',
            ])->name('announcements.store');

            Route::get('/annonces/{announcement}', [
                Communication\AnnouncementController::class,
                'show',
            ])->name('announcements.show');

            Route::post('/annonces/{announcement}/cancel', [
                Communication\AnnouncementController::class,
                'cancel',
            ])->name('announcements.cancel');

            Route::post('/annonces/{announcement}/resend', [
                Communication\AnnouncementController::class,
                'resend',
            ])->name('announcements.resend');

            Route::delete('/annonces/{announcement}', [
                Communication\AnnouncementController::class,
                'destroy',
            ])->name('announcements.destroy');
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
                App\Http\Controllers\Censeur\DashboardController::class
            )->name('dashboard');

            Route::get('/evaluations', [
                EvaluationController::class,
                'index',
            ])->name('evaluations.index');

            Route::get('/evaluations/create', [
                EvaluationController::class,
                'create',
            ])->name('evaluations.create');

            Route::post('/evaluations', [
                EvaluationController::class,
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

            Route::post('/evaluations/{evaluation}/validate', [
                App\Http\Controllers\Censeur\EvaluationValidationController::class,
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

            Route::post('/academic/results/export-educmaster', [
                Admin\AcademicResultController::class,
                'exportEducMaster',
            ])->name('academic.results.export-educmaster');

            Route::get('/academic/results/prepare-educmaster', [
                Admin\AcademicResultController::class,
                'prepareEducMaster',
            ])->name('academic.results.prepare-educmaster');

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
                AttendanceController::class,
                'index',
            ])->name('attendance.index');

            Route::get('/attendance/create', [
                AttendanceController::class,
                'create',
            ])->name('attendance.create');

            Route::post('/attendance', [
                AttendanceController::class,
                'store',
            ])->name('attendance.store');

            Route::get('/attendance/students/{student}', [
                AttendanceController::class,
                'studentHistory',
            ])->name('attendance.students.show');

            Route::put('/attendance/{attendance}', [
                AttendanceController::class,
                'update',
            ])->name('attendance.update');

            Route::post('/attendance/{attendance}/justify', [
                AttendanceController::class,
                'reviewJustification',
            ])->name('attendance.justify');

            Route::post('/attendance/validate', [
                AttendanceController::class,
                'validateRecords',
            ])->name('attendance.validate');

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

            /*
            | Emploi du temps — contrôle et planification
            |
            | Le censeur gère l'emploi du temps : création, modification et
            | suppression des créneaux, comme l'administration.
            */

            Route::get('/timetables', [
                Admin\TimetableController::class,
                'index',
            ])->name('timetables.index');

            Route::post('/timetables', [
                Admin\TimetableController::class,
                'store',
            ])->name('timetables.store');

            Route::put('/timetables/{timetable}', [
                Admin\TimetableController::class,
                'update',
            ])->name('timetables.update');

            Route::delete('/timetables/{timetable}', [
                Admin\TimetableController::class,
                'destroy',
            ])->name('timetables.destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Surveillant — vie scolaire (lecture & assiduité)
    |--------------------------------------------------------------------------
    |
    | Centré sur la discipline et l'assiduité : retards, absences et exécution
    | de l'emploi du temps. Pas d'accès aux évaluations ni aux paramètres.
    */

    Route::middleware('role:surveillant')
        ->prefix('surveillant')
        ->name('surveillant.')
        ->group(function () {

            Route::get('/dashboard',
                App\Http\Controllers\Surveillant\DashboardController::class
            )->name('dashboard');

            /*
            | Emploi du temps — lecture seule
            */

            Route::get('/timetables', [
                Admin\TimetableController::class,
                'index',
            ])->name('timetables.index');

            /*
            | Assiduité — retards & absences
            */

            Route::get('/attendance', [
                AttendanceController::class,
                'index',
            ])->name('attendance.index');

            Route::get('/attendance/create', [
                AttendanceController::class,
                'create',
            ])->name('attendance.create');

            Route::post('/attendance', [
                AttendanceController::class,
                'store',
            ])->name('attendance.store');

            Route::get('/attendance/students/{student}', [
                AttendanceController::class,
                'studentHistory',
            ])->name('attendance.students.show');

            Route::put('/attendance/{attendance}', [
                AttendanceController::class,
                'update',
            ])->name('attendance.update');

            Route::post('/attendance/{attendance}/justify', [
                AttendanceController::class,
                'reviewJustification',
            ])->name('attendance.justify');

            Route::post('/attendance/validate', [
                AttendanceController::class,
                'validateRecords',
            ])->name('attendance.validate');

            /*
            | Élèves & classes — lecture
            */

            Route::get('/students', [
                Admin\StudentController::class,
                'index',
            ])->name('students.index');

            Route::get('/classes', [
                Admin\ClassRoomController::class,
                'index',
            ])->name('classes.index');
        });

    /*
    |--------------------------------------------------------------------------
    | Direction & Comptabilité — pilotage en lecture
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Comptabilité — pilotage financier en lecture
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:accountant')
        ->group(function () {

            Route::get('/comptabilite/tableau-de-bord', ManagementDashboardController::class)
                ->name('accountant.dashboard');
        });

    /*
    |--------------------------------------------------------------------------
    | Directeur Primaire — gestion pédagogique (primaire uniquement)
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:director')
        ->prefix('director')
        ->name('director.')
        ->group(function () {

            Route::get('/dashboard',
                DirectorDashboardController::class
            )->name('dashboard');

            Route::get('/assistant', [
                AssistantController::class,
                'index',
            ])->name('assistant.index');

            Route::post('/assistant/ask', [
                AssistantController::class,
                'ask',
            ])->name('assistant.ask');

            Route::get('/evaluations', [
                DirectorEvaluationController::class,
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

            Route::post('/academic/results/export-educmaster', [
                Admin\AcademicResultController::class,
                'exportEducMaster',
            ])->name('academic.results.export-educmaster');

            Route::get('/academic/results/prepare-educmaster', [
                Admin\AcademicResultController::class,
                'prepareEducMaster',
            ])->name('academic.results.prepare-educmaster');

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
                AttendanceController::class,
                'index',
            ])->name('attendance.index');

            Route::get('/attendance/create', [
                AttendanceController::class,
                'create',
            ])->name('attendance.create');

            Route::post('/attendance', [
                AttendanceController::class,
                'store',
            ])->name('attendance.store');

            Route::get('/attendance/students/{student}', [
                AttendanceController::class,
                'studentHistory',
            ])->name('attendance.students.show');

            Route::put('/attendance/{attendance}', [
                AttendanceController::class,
                'update',
            ])->name('attendance.update');

            Route::post('/attendance/{attendance}/justify', [
                AttendanceController::class,
                'reviewJustification',
            ])->name('attendance.justify');

            Route::post('/attendance/validate', [
                AttendanceController::class,
                'validateRecords',
            ])->name('attendance.validate');

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

            Route::get('/students', [
                Admin\StudentController::class,
                'index',
            ])->name('students.index');

            Route::get('/students/at-risk', [
                Admin\StudentFollowUpController::class,
                'index',
            ])->name('students.at-risk');

            Route::get('/students/{student}/follow-up', [
                Admin\StudentFollowUpController::class,
                'show',
            ])->name('students.follow-up');

            Route::post('/students/{student}/follow-up', [
                Admin\StudentFollowUpController::class,
                'store',
            ])->name('students.follow-up.store');

            Route::get('/guardians', [
                Admin\GuardianController::class,
                'index',
            ])->name('guardians.index');

            Route::get('/timetables', [
                Admin\TimetableController::class,
                'index',
            ])->name('timetables.index');

            /*
            | Centre de documents (phase 6)
            */

            Route::get('/documents', [
                Admin\DocumentCenterController::class,
                'index',
            ])->name('documents.index');

            Route::get('/documents/statistics', [
                Admin\DocumentCenterController::class,
                'statistics',
            ])->name('documents.statistics');

            Route::get('/documents/students', [
                Admin\DocumentCenterController::class,
                'studentsList',
            ])->name('documents.students');

            Route::get('/documents/classes', [
                Admin\DocumentCenterController::class,
                'roomList',
            ])->name('documents.classes');

            Route::get('/documents/classes/{classRoom}', [
                Admin\DocumentCenterController::class,
                'classList',
            ])->name('documents.class-list');

            Route::get('/documents/students/{student}/certificate', [
                Admin\DocumentCenterController::class,
                'certificate',
            ])->name('documents.certificate');

            Route::get('/documents/students/{student}/attestation', [
                Admin\DocumentCenterController::class,
                'attestation',
            ])->name('documents.attestation');

            /*
            | Préinscriptions (phase 7) — périmètre primaire
            */

            Route::get('/preinscriptions', [
                Admin\PreinscriptionController::class,
                'index',
            ])->name('preinscriptions.index');

            Route::post('/preinscriptions/{preinscription}/approve', [
                Admin\PreinscriptionController::class,
                'approve',
            ])->name('preinscriptions.approve');

            Route::post('/preinscriptions/{preinscription}/reject', [
                Admin\PreinscriptionController::class,
                'reject',
            ])->name('preinscriptions.reject');
        });

    /*
    |--------------------------------------------------------------------------
    | Teacher / Secretary — Evaluation Workflow
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:teacher,secretary,gardien,agent_entretien')
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
            | Emploi du temps de l'enseignant
            */

            Route::get('/timetable', Teacher\TimetableController::class)
                ->middleware('role:teacher')
                ->name('timetable');

            /*
            | Banque d'épreuves (consultation)
            */

            Route::middleware('role:teacher')->prefix('banque-epreuves')->name('exam-bank.')->group(function () {
                Route::get('/', [ExamBankController::class, 'index'])->name('index');
                Route::get('/{examResource}', [ExamBankController::class, 'show'])->name('show');
                Route::get('/{examResource}/attachments/{attachment}/download', [ExamBankController::class, 'download'])->name('download');
                Route::get('/{examResource}/attachments/{attachment}/answer-key', [ExamBankController::class, 'downloadAnswerKey'])->name('answer-key');
            });

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

            Route::post('/academic/results/export-educmaster', [
                Admin\AcademicResultController::class,
                'exportEducMaster',
            ])->name('academic.results.export-educmaster');

            Route::get('/academic/results/prepare-educmaster', [
                Admin\AcademicResultController::class,
                'prepareEducMaster',
            ])->name('academic.results.prepare-educmaster');

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
                AttendanceController::class,
                'index',
            ])->name('attendance.index');

            Route::get('/attendance/create', [
                AttendanceController::class,
                'create',
            ])->name('attendance.create');

            Route::post('/attendance', [
                AttendanceController::class,
                'store',
            ])->name('attendance.store');

            Route::get('/attendance/students/{student}', [
                AttendanceController::class,
                'studentHistory',
            ])->name('attendance.students.show');

            Route::put('/attendance/{attendance}', [
                AttendanceController::class,
                'update',
            ])->name('attendance.update');

            Route::post('/attendance/{attendance}/justify', [
                AttendanceController::class,
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
                App\Http\Controllers\Parent\DashboardController::class
            )->name('dashboard');

            /*
            | Children
            */

            Route::get('/children/{student}', [
                ChildController::class,
                'show',
            ])->name('children.show');

            /*
            | Attendance
            */

            Route::get('/children/{student}/attendance', [
                ChildController::class,
                'attendance',
            ])->name('children.attendance');

            Route::get('/children/{student}/report-cards', [
                ChildController::class,
                'reportCards',
            ])->name('children.report-cards');

            /*
            | Emploi du temps de l'enfant
            */

            Route::get('/children/{student}/timetable', TimetableController::class)
                ->name('children.timetable');

            Route::get('/report-cards/{reportCard}', [
                Admin\ReportCardController::class,
                'show',
            ])->name('report-cards.show');

            Route::get('/report-cards/{reportCard}/pdf', [
                Admin\ReportCardController::class,
                'download',
            ])->name('report-cards.pdf');

            Route::post('/attendance/{attendance}/justification', [
                ChildController::class,
                'justify',
            ])->name('attendance.justify');

            /*
            | Scolarité — historique des paiements, relevé et reçus
            */

            Route::get('/finance', [FinanceController::class, 'index'])
                ->name('finance.index');

            Route::get('/finance/statement', [FinanceController::class, 'statement'])
                ->name('finance.statement');

            Route::get('/finance/payments/{payment}/receipt', [FinanceController::class, 'receipt'])
                ->name('finance.receipt');

            /*
            | Messages adressés à l'établissement
            */

            Route::get('/messages', [MessageController::class, 'index'])
                ->name('messages.index');

            Route::post('/messages', [MessageController::class, 'store'])
                ->name('messages.store');

            /*
            | Notifications push (PWA)
            */

            Route::get('/notifications', [NotificationSettingsController::class, 'edit'])
                ->name('notifications.edit');

            Route::post('/notifications/test', [NotificationSettingsController::class, 'test'])
                ->name('notifications.test');
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
                App\Http\Controllers\Secretary\DashboardController::class
            )->name('dashboard');

            /*
            | Emploi du temps — lecture seule
            */

            Route::get('/timetables', [
                Admin\TimetableController::class,
                'index',
            ])->name('timetables.index');

            /*
            | Finance — la secrétaire saisit dépenses, salaires, ventes et contributions
            */

            Route::get('/depenses', [
                Finance\ExpenseController::class,
                'index',
            ])->name('expenses.index');

            Route::post('/depenses', [
                Finance\ExpenseController::class,
                'store',
            ])->name('expenses.store');

            Route::get('/salaires', [
                Finance\SalaryController::class,
                'index',
            ])->name('salaries.index');

            Route::post('/salaires', [
                Finance\SalaryController::class,
                'save',
            ])->name('salaries.save');

            Route::get('/ventes', [
                Finance\SaleController::class,
                'index',
            ])->name('sales.index');

            Route::post('/ventes', [
                Finance\SaleController::class,
                'store',
            ])->name('sales.store');

            Route::get('/contributions', [
                Finance\ContributionController::class,
                'index',
            ])->name('contributions.index');

            Route::post('/contributions', [
                Finance\ContributionController::class,
                'store',
            ])->name('contributions.store');
        });

    /*
    |--------------------------------------------------------------------------
    | Messages des parents — boîte de réception du personnel
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin,censeur,secretary')
        ->prefix('messages')
        ->name('messages.')
        ->group(function () {

            Route::get('/', [
                StaffParentMessageController::class,
                'index',
            ])->name('index');

            Route::get('/{message}', [
                StaffParentMessageController::class,
                'show',
            ])->name('show');
        });
});

/*
|--------------------------------------------------------------------------
| Site public des établissements (URL à la racine)
|--------------------------------------------------------------------------
|
| Ex. https://www.corischool.com/ketry-school
|
| Cette route est déclarée en dernier : elle ne doit capturer que les
| premiers segments non reconnus, afin de ne jamais masquer une route
| applicative existante (d'où l'exclusion des slugs réservés).
|
*/

Route::get('/{slug}', [
    PublicSchoolController::class,
    'bySlug',
])
    ->where(
        'slug',
        '(?!(?:'.implode('|', array_map(
            fn (string $slug) => preg_quote($slug, '/'),
            ReservedSchoolSlugs::all()
        )).')$)[a-z0-9][a-z0-9\-]*'
    )
    ->name('public.school.slug');
