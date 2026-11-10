<?php

namespace Tests\Feature\ParentAccess;

use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Jobs\SendParentAccessCodeJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\ParentAccessCode;
use App\Models\ParentAccessSession;
use App\Models\ParentGuardian;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\ParentAccess\ParentAccessCodeService;
use App\Services\ParentAccess\ParentAccessNotificationService;
use App\Services\ParentAccess\ParentAccessSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ParentAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsVerified(User $user): static
    {
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $this->withSession([
            'two_factor_verified_at' => now()->timestamp,
        ])->actingAs($user);
    }

    private function configureTwilio(): void
    {
        config([
            'corischool.parent_access.channel' => 'whatsapp',
            'services.whatsapp.provider' => 'twilio',
            'corischool.parent_access.whatsapp_provider' => 'twilio',
            'services.twilio.account_sid' => 'ACtest',
            'services.twilio.auth_token' => 'test-token',
            'services.twilio.whatsapp_from' => 'whatsapp:+14155238886',
            'services.twilio.templates.verification_code_parent.content_sid' => 'HXcode',
            'services.twilio.templates.espace_parent.content_sid' => 'HXspace',
        ]);
    }

    private function configureSms(): void
    {
        config([
            'corischool.parent_access.channel' => 'sms',
            'corischool.parent_access.sms_message' => 'CoriSchool : votre accès parent : {code}. Visitez votre espace ici : {url}.',
            'corischool.parent_access.sms_url' => 'https://corischool.com/ecole/{slug}',
            'services.sms.provider' => 'emc',
            'services.sms.emc.enabled' => true,
            'services.sms.emc.api_url' => 'https://api.e-mc.co/v3',
            'services.sms.emc.api_key' => null,
            'services.sms.emc.username' => 'corisch',
            'services.sms.emc.password' => 'secret',
            'services.sms.emc.sender' => 'CoriSchool',
            'services.sms.emc.timeout' => 15,
            'services.sms.emc.country_code' => '229',
        ]);
    }

    public function test_admin_can_manage_parent_access(): void
    {
        $school = School::factory()->create();
        ParentGuardian::factory()->create(['school_id' => $school->id]);
        $admin = User::factory()->forSchool($school)->admin()->create();

        $this->actingAsVerified($admin)
            ->get('/admin/parents/access')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Parents/Access')
                ->where('routePrefix', 'admin')
                ->where('schoolId', $school->id)
                ->has('parents.data', 1));
    }

    public function test_censeur_can_manage_parent_access(): void
    {
        $school = School::factory()->create();
        ParentGuardian::factory()->create(['school_id' => $school->id]);
        $censeur = User::factory()->forSchool($school)->censeur()->create();

        $this->actingAsVerified($censeur)
            ->get('/censeur/parents/access')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Parents/Access')
                ->where('routePrefix', 'censeur')
                ->where('schoolId', $school->id)
                ->has('parents.data', 1));
    }

    public function test_platform_admin_can_manage_parent_access_for_a_school(): void
    {
        $school = School::factory()->create();
        ParentGuardian::factory()->create(['school_id' => $school->id]);
        $platformAdmin = User::factory()->withoutSchool()->create();
        $platformAdmin->forceFill(['role' => Role::PlatformAdmin])->save();

        $this->actingAsVerified($platformAdmin)
            ->get("/platform/schools/{$school->id}/parents/access")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Parents/Access')
                ->where('routePrefix', 'platform')
                ->where('schoolId', $school->id)
                ->has('parents.data', 1));
    }

    public function test_platform_admin_can_generate_a_code_for_a_school_parent(): void
    {
        Queue::fake();

        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $platformAdmin = User::factory()->withoutSchool()->create();
        $platformAdmin->forceFill(['role' => Role::PlatformAdmin])->save();

        $this->actingAsVerified($platformAdmin)
            ->post("/platform/schools/{$school->id}/parents/access/{$parent->id}/generate")
            ->assertRedirect();

        $this->assertDatabaseHas('parent_access_codes', [
            'parent_id' => $parent->id,
            'school_id' => $school->id,
            'created_by' => $platformAdmin->id,
        ]);
    }

    public function test_teacher_cannot_manage_parent_access(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->forSchool($school)->teacher()->create();

        $this->actingAsVerified($teacher)
            ->get('/admin/parents/access')
            ->assertForbidden();

        $this->actingAsVerified($teacher)
            ->get('/censeur/parents/access')
            ->assertForbidden();
    }

    public function test_admin_can_generate_and_queue_codes_for_all_parents(): void
    {
        Queue::fake();
        $this->configureSms();

        $school = School::factory()->create();
        $admin = User::factory()->forSchool($school)->admin()->create();

        foreach (range(1, 2) as $index) {
            $parent = ParentGuardian::factory()->create([
                'school_id' => $school->id,
                'phone' => '+2299000000'.$index,
            ]);
            $student = Student::factory()->create(['school_id' => $school->id]);
            $parent->students()->attach($student->id, ['relationship' => 'père']);
        }

        $this->actingAsVerified($admin)
            ->post('/admin/parents/access/generate-all')
            ->assertRedirect()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Envoi SMS programmé par lots'));

        $this->assertDatabaseCount('parent_access_codes', 2);
        Queue::assertPushed(SendParentAccessCodeJob::class, 2);

        $this->actingAsVerified($admin)
            ->get('/admin/parents/access')
            ->assertInertia(fn (Assert $page) => $page
                ->has('parents.data', 2)
                ->where('parents.data.0.latest_code.plain_code', fn ($code) => is_string($code) && str_starts_with($code, 'CS-')));
    }

    public function test_generating_a_code_queues_sms_and_returns_the_code(): void
    {
        Queue::fake();
        $this->configureSms();

        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create([
            'school_id' => $school->id,
            'phone' => '+22990000001',
        ]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);
        $admin = User::factory()->forSchool($school)->admin()->create();

        $this->actingAsVerified($admin)
            ->post("/admin/parents/access/{$parent->id}/generate")
            ->assertRedirect()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Envoi SMS programmé'))
            ->assertSessionHas('generated_code', fn ($code) => is_string($code) && str_starts_with($code, 'CS-'))
            ->assertSessionHas('parent_name', $parent->name);

        $code = ParentAccessCode::query()->firstOrFail();

        Queue::assertPushed(SendParentAccessCodeJob::class, fn ($job) => $job->parentAccessCodeId === $code->id);

        $this->assertDatabaseHas('parent_access_codes', [
            'parent_id' => $parent->id,
            'school_id' => $school->id,
        ]);
    }

    public function test_access_code_job_sends_both_twilio_templates(): void
    {
        $this->configureTwilio();

        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
        ]);

        $school = School::factory()->create(['name' => 'École Les Palmiers']);
        $parent = ParentGuardian::factory()->create([
            'school_id' => $school->id,
            'name' => 'Ibrahim TRAORE',
            'phone' => '+22990000001',
        ]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $result = app(ParentAccessCodeService::class)->generateCode($parent, $school);

        (new SendParentAccessCodeJob($result['code']->id))
            ->handle(app(ParentAccessNotificationService::class));

        Http::assertSentCount(2);

        Http::assertSent(function ($request) use ($result) {
            $variables = json_decode($request['ContentVariables'], true);

            return $request['ContentSid'] === 'HXcode'
                && $request['To'] === 'whatsapp:+22990000001'
                && $variables['1'] === $result['plain_code'];
        });

        Http::assertSent(function ($request) use ($parent, $school) {
            $variables = json_decode($request['ContentVariables'], true);

            return $request['ContentSid'] === 'HXspace'
                && $variables['1'] === $parent->name
                && $variables['2'] === $school->name
                && $variables['3'] === $school->slug;
        });
    }

    public function test_access_code_job_sends_an_emc_sms_with_code_and_school_link(): void
    {
        $this->configureSms();

        Http::fake([
            'https://api.e-mc.co/v3/sms' => Http::response([
                'data' => ['success' => true, 'messageId' => 'emc-123'],
            ], 200),
        ]);

        $school = School::factory()->create([
            'name' => 'École Les Palmiers',
            'slug' => 'les-palmiers',
        ]);
        $parent = ParentGuardian::factory()->create([
            'school_id' => $school->id,
            'name' => 'Ibrahim TRAORE',
            'phone' => '+22997000000',
        ]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $result = app(ParentAccessCodeService::class)->generateCode($parent, $school);

        (new SendParentAccessCodeJob($result['code']->id))
            ->handle(app(ParentAccessNotificationService::class));

        Http::assertSentCount(1);

        Http::assertSent(function ($request) use ($result) {
            return $request->url() === 'https://api.e-mc.co/v3/sms'
                && $request['from'] === 'CoriSchool'
                && $request['to'] === '2290197000000'
                && $request['content'] === "CoriSchool : votre accès parent : {$result['plain_code']}. Visitez votre espace ici : https://corischool.com/ecole/les-palmiers.";
        });
    }

    public function test_new_login_revokes_previous_parent_sessions_when_only_one_device_is_allowed(): void
    {
        config(['corischool.parent_access.max_devices' => 1]);

        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $firstSession = app(ParentAccessSessionService::class)
            ->createSession($parent, $school, null, request());

        $result = app(ParentAccessCodeService::class)->generateCode($parent, $school);

        $this->post("/{$school->slug}/parent/login", ['code' => $result['plain_code']])
            ->assertRedirect("/{$school->slug}/parent-access/dashboard");

        $sessions = ParentAccessSession::query()->orderBy('id')->get();

        $this->assertCount(2, $sessions);
        $this->assertNotNull($sessions[0]->revoked_at, 'L\'ancienne session doit être révoquée.');
        $this->assertNull($sessions[1]->revoked_at, 'La nouvelle session doit rester active.');

        $this->withCookie('parent_access_token', $firstSession['plain_token'])
            ->get("/{$school->slug}/parent-access/dashboard")
            ->assertRedirect("/{$school->slug}/parent/login")
            ->assertSessionHasErrors('code');
    }

    public function test_print_page_shows_the_latest_valid_code(): void
    {
        Queue::fake();

        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);
        $admin = User::factory()->forSchool($school)->admin()->create();

        $this->actingAsVerified($admin)
            ->post("/admin/parents/access/{$parent->id}/generate")
            ->assertRedirect();

        $this->actingAsVerified($admin)
            ->get("/admin/parents/access/{$parent->id}/print")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Parents/AccessPrint')
                ->where('generated_code', fn ($code) => is_string($code) && str_starts_with($code, 'CS-')));
    }

    public function test_parent_can_enter_a_valid_code_to_open_the_parent_space(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $result = app(ParentAccessCodeService::class)->generateCode($parent, $school);

        $this->post("/{$school->slug}/parent/login", ['code' => $result['plain_code']])
            ->assertRedirect("/{$school->slug}/parent-access/dashboard")
            ->assertCookie('parent_access_token');

        $this->assertDatabaseHas('parent_access_sessions', [
            'parent_id' => $parent->id,
            'school_id' => $school->id,
            'revoked_at' => null,
        ]);
        $this->assertNotNull($result['code']->fresh()->used_at);
    }

    public function test_parent_login_creates_session_and_cookie_until_end_of_academic_year(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);
        $year = AcademicYear::factory()->forSchool($school)->create([
            'starts_on' => now()->startOfYear(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
        ]);

        $result = app(ParentAccessCodeService::class)->generateCode($parent, $school, $year);

        $response = $this->post("/{$school->slug}/parent/login", ['code' => $result['plain_code']])
            ->assertRedirect("/{$school->slug}/parent-access/dashboard")
            ->assertCookie('parent_access_token');

        $session = ParentAccessSession::query()->firstOrFail();

        $this->assertSame(
            $year->ends_on->copy()->endOfDay()->toDateTimeString(),
            $session->expires_at->toDateTimeString(),
            'La session doit expirer à la fin de l\'année scolaire.'
        );

        $cookie = collect($response->baseResponse->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'parent_access_token');

        $this->assertNotNull($cookie);
        $this->assertGreaterThan(
            now()->addMonths(5)->timestamp,
            $cookie->getExpiresTime(),
            'Le cookie doit rester valide jusqu\'à la fin de l\'année scolaire.'
        );
    }

    public function test_parent_session_remains_valid_after_the_access_code_expires(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);
        $year = AcademicYear::factory()->forSchool($school)->create([
            'starts_on' => now()->startOfYear(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'is_current' => true,
        ]);

        $session = app(ParentAccessSessionService::class)
            ->createSession($parent, $school, $year, request());

        $this->travel(1441)->minutes();

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->get("/{$school->slug}/parent-access/dashboard")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Dashboard')
                ->where('space', 'parent-access'));
    }

    public function test_parent_space_redirects_to_login_when_session_cookie_is_missing(): void
    {
        $school = School::factory()->create();

        $this->get("/{$school->slug}/parent-access/dashboard")
            ->assertRedirect("/{$school->slug}/parent/login");
    }

    public function test_admin_sees_activated_status_after_parent_login(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);
        $admin = User::factory()->forSchool($school)->admin()->create();

        $result = app(ParentAccessCodeService::class)->generateCode($parent, $school);

        $this->post("/{$school->slug}/parent/login", ['code' => $result['plain_code']])
            ->assertRedirect("/{$school->slug}/parent-access/dashboard");

        $this->actingAsVerified($admin)
            ->get('/admin/parents/access')
            ->assertInertia(fn (Assert $page) => $page
                ->has('parents.data', 1)
                ->where('parents.data.0.status', 'activated')
                ->where('parents.data.0.active_sessions_count', 1)
                ->where('parents.data.0.latest_session.is_active', true));
    }

    public function test_access_code_expires_after_one_day(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $result = app(ParentAccessCodeService::class)->generateCode($parent, $school);

        $this->assertTrue(
            $result['code']->expires_at->between(now()->addMinutes(1439), now()->addMinutes(1441)),
            'Le code doit expirer un jour après sa génération.'
        );

        $this->travel(1441)->minutes();

        $this->post("/{$school->slug}/parent/login", ['code' => $result['plain_code']])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('parent_access_sessions', 0);
    }

    public function test_parent_cannot_enter_an_invalid_code(): void
    {
        $school = School::factory()->create();

        $this->post("/{$school->slug}/parent/login", ['code' => 'CS-INVALID'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('parent_access_sessions', 0);
    }

    public function test_parent_session_can_open_the_dashboard(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $session = app(ParentAccessSessionService::class)
            ->createSession($parent, $school, null, request());

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->get("/{$school->slug}/parent-access/dashboard")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Dashboard')
                ->where('space', 'parent-access')
                ->has('children', 1)
                ->where('parent.name', $parent->name));
    }

    public function test_parent_access_can_open_the_full_parent_space(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $session = app(ParentAccessSessionService::class)
            ->createSession($parent, $school, null, request());

        $this->withCookie('parent_access_token', $session['plain_token']);

        $this->get('/parent-access/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Dashboard')
                ->where('space', 'parent-access'));

        $this->get("/parent-access/children/{$student->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Child')
                ->where('space', 'parent-access')
                ->where('student.id', $student->id));

        $this->get("/parent-access/children/{$student->id}/attendance")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Attendance')
                ->where('space', 'parent-access'));

        $this->get("/parent-access/children/{$student->id}/report-cards")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/ReportCards/Index')
                ->where('space', 'parent-access'));

        $this->get("/parent-access/children/{$student->id}/timetable")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Timetable')
                ->where('space', 'parent-access'));

        $this->get('/parent-access/finance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Finance/Index')
                ->where('space', 'parent-access'));

        $this->get('/parent-access/messages')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Messages/Index')
                ->where('space', 'parent-access'));
    }

    public function test_parent_access_cannot_open_another_school_child(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $foreignStudent = Student::factory()->create(['school_id' => $otherSchool->id]);

        $session = app(ParentAccessSessionService::class)
            ->createSession($parent, $school, null, request());

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->get("/parent-access/children/{$foreignStudent->id}")
            ->assertForbidden();
    }

    public function test_parent_access_can_send_a_message_to_the_school(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $session = app(ParentAccessSessionService::class)
            ->createSession($parent, $school, null, request());

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->post('/parent-access/messages', [
                'subject' => 'Question sur la scolarité',
                'body' => 'Bonjour, je souhaite un éclaircissement.',
                'student_id' => $student->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('parent_messages', [
            'school_id' => $school->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'subject' => 'Question sur la scolarité',
        ]);
    }

    public function test_parent_access_can_open_a_published_report_card(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $classRoom = ClassRoom::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'class_id' => $classRoom->id,
        ]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $period = AcademicPeriod::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'starts_at' => now()->startOfYear()->toDateString(),
            'ends_at' => now()->endOfYear()->toDateString(),
        ]);

        $reportCard = ReportCard::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'academic_year_id' => $year->id,
            'academic_period_id' => $period->id,
            'status' => ReportCardStatus::Published,
            'version' => 1,
            'verification_token' => str()->random(64),
            'published_at' => now(),
            'snapshot' => [
                'student' => ['name' => $student->full_name, 'matricule' => $student->matricule],
                'class' => ['name' => $classRoom->name],
                'academic_year' => ['name' => $year->name],
                'period' => ['name' => $period->name],
                'school' => ['name' => $school->name],
            ],
        ]);

        $session = app(ParentAccessSessionService::class)
            ->createSession($parent, $school, $year, request());

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->get("/parent-access/report-cards/{$reportCard->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Academic/ReportCards/Show')
                ->where('space', 'parent-access')
                ->where('routePrefix', 'parent-access')
                ->where('canEditComments', false)
                ->where('reportCard.id', $reportCard->id));
    }

    public function test_parent_access_cannot_open_another_school_report_card(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);

        $classRoom = ClassRoom::factory()->create(['school_id' => $otherSchool->id]);
        $student = Student::factory()->create([
            'school_id' => $otherSchool->id,
            'class_id' => $classRoom->id,
        ]);
        $year = AcademicYear::factory()->create(['school_id' => $otherSchool->id]);
        $period = AcademicPeriod::create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'starts_at' => now()->startOfYear()->toDateString(),
            'ends_at' => now()->endOfYear()->toDateString(),
        ]);
        $reportCard = ReportCard::create([
            'school_id' => $otherSchool->id,
            'student_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'academic_year_id' => $year->id,
            'academic_period_id' => $period->id,
            'status' => ReportCardStatus::Published,
            'version' => 1,
            'verification_token' => str()->random(64),
            'published_at' => now(),
        ]);

        $session = app(ParentAccessSessionService::class)
            ->createSession($parent, $school, null, request());

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->get("/parent-access/report-cards/{$reportCard->id}")
            ->assertForbidden();
    }

    public function test_parent_access_can_submit_an_attendance_justification(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $classRoom = ClassRoom::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'class_id' => $classRoom->id,
        ]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $period = AcademicPeriod::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'starts_at' => now()->startOfYear()->toDateString(),
            'ends_at' => now()->endOfYear()->toDateString(),
        ]);
        $attendance = AttendanceRecord::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'academic_period_id' => $period->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        $session = app(ParentAccessSessionService::class)
            ->createSession($parent, $school, $year, request());

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->post("/parent-access/attendance/{$attendance->id}/justification", [
                'reason' => 'Rendez-vous médical.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_justifications', [
            'attendance_record_id' => $attendance->id,
            'parent_id' => $parent->id,
            'reason' => 'Rendez-vous médical.',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'action' => 'attendance.justification_submitted',
        ]);
    }
}
