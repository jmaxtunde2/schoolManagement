<?php

namespace Tests\Feature\ParentAccess;

use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\ParentAccess\ParentAccessCodeService;
use App\Services\ParentAccess\ParentAccessSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_admin_can_generate_codes_for_all_parents_and_send_them_by_whatsapp(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
        ]);

        config([
            'services.whatsapp.provider' => 'meta',
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.api_url' => 'https://graph.facebook.com',
            'services.whatsapp.graph_version' => 'v21.0',
        ]);

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
            ->assertRedirect();

        $this->assertDatabaseCount('parent_access_codes', 2);
        // Deux modèles distincts par parent : le code seul puis l'invitation.
        Http::assertSentCount(4);

        Http::assertSent(function ($request) {
            return $request['type'] === 'template'
                && $request['template']['name'] === 'verification_code_parent'
                && count($request['template']['components'][0]['parameters']) === 1
                && str_starts_with($request['template']['components'][0]['parameters'][0]['text'], 'CS-');
        });

        Http::assertSent(function ($request) use ($school) {
            $components = collect($request['template']['components'] ?? []);

            $body = $components->firstWhere('type', 'body');
            $button = $components->firstWhere('type', 'button');

            return $request['type'] === 'template'
                && $request['template']['name'] === 'espace_parent'
                && $body['parameters'][0]['text'] !== ''
                && $body['parameters'][1]['text'] === $school->name
                && $button['parameters'][0]['text'] === $school->slug;
        });

        $this->actingAsVerified($admin)
            ->get('/admin/parents/access')
            ->assertInertia(fn (Assert $page) => $page
                ->has('parents.data', 2)
                ->where('parents.data.0.latest_code.plain_code', fn ($code) => is_string($code) && str_starts_with($code, 'CS-')));
    }

    public function test_generating_a_code_sends_the_verification_and_parent_space_templates(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
        ]);

        config([
            'services.whatsapp.provider' => 'meta',
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.api_url' => 'https://graph.facebook.com',
            'services.whatsapp.graph_version' => 'v21.0',
        ]);

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
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'deux messages WhatsApp'));

        Http::assertSentCount(2);

        Http::assertSent(function ($request) use ($parent, $school) {
            return $request['template']['name'] === 'espace_parent'
                && $request['template']['components'][0]['parameters'][0]['text'] === $parent->name
                && $request['template']['components'][0]['parameters'][1]['text'] === $school->name;
        });
    }

    public function test_generating_a_code_sends_both_templates_through_wati(): void
    {
        Http::fake(function ($request) {
            $recipient = $request->data()['recipients'][0] ?? [];

            return Http::response([
                'success' => true,
                'recipients' => [[
                    'local_message_id' => $recipient['local_message_id'] ?? null,
                    'errors' => [],
                ]],
            ], 200);
        });

        config([
            'services.whatsapp.provider' => 'wati',
            'services.whatsapp.wati.enabled' => true,
            'services.whatsapp.wati.api_key' => 'fake-wati-key',
            'services.whatsapp.wati.api_url' => 'https://wati.test',
            'services.whatsapp.wati.channel_number' => '22969422974',
            'services.whatsapp.wati.template_cache_ttl' => 0,
            'services.whatsapp.wati.templates.verification_code_parent.body_parameter_names' => ['1'],
            'services.whatsapp.wati.templates.espace_parent.body_parameter_names' => ['1', '2'],
            'services.whatsapp.wati.templates.espace_parent.url_parameter_name' => '3',
        ]);

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
            ->assertRedirect();

        Http::assertSentCount(2);

        Http::assertSent(function ($request) {
            $params = collect($request->data()['recipients'][0]['custom_params'] ?? [])
                ->pluck('value', 'name')
                ->all();

            return $request['template_name'] === 'verification_code_parent'
                && str_starts_with($params['1'], 'CS-');
        });

        Http::assertSent(function ($request) use ($parent, $school) {
            $params = collect($request->data()['recipients'][0]['custom_params'] ?? [])
                ->pluck('value', 'name')
                ->all();

            return $request['template_name'] === 'espace_parent'
                && $params['1'] === $parent->name
                && $params['2'] === $school->name
                && $params['3'] === $school->slug;
        });
    }

    public function test_print_page_shows_the_latest_valid_code(): void
    {
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

    public function test_access_code_expires_after_one_hour(): void
    {
        $school = School::factory()->create();
        $parent = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $parent->students()->attach($student->id, ['relationship' => 'père']);

        $result = app(ParentAccessCodeService::class)->generateCode($parent, $school);

        $this->assertTrue(
            $result['code']->expires_at->between(now()->addMinutes(59), now()->addMinutes(61)),
            'Le code doit expirer une heure après sa génération.'
        );

        $this->travel(61)->minutes();

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
