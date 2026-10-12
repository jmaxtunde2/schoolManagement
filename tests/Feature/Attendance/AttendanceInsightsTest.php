<?php

namespace Tests\Feature\Attendance;

use App\Actions\Attendance\BuildAttendanceEvolution;
use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Tableaux de bord « retards & absences » et évolution sur une période.
 */
class AttendanceInsightsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->classRoom = ClassRoom::factory()->create(['school_id' => $this->school->id]);
    }

    private function record(string $status, string $date, ?int $delay = null, ?Student $student = null): AttendanceRecord
    {
        $student ??= Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
        ]);

        return AttendanceRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'class_room_id' => $this->classRoom->id,
            'attendance_date' => $date,
            'status' => $status,
            'delay_minutes' => $delay,
        ]);
    }

    public function test_evolution_buckets_group_by_day_and_stay_school_scoped(): void
    {
        $admin = User::factory()->admin()->forSchool($this->school)->create();
        $this->actingAs($admin);

        $this->record(AttendanceStatus::Absent->value, '2026-10-01');
        $this->record(AttendanceStatus::Late->value, '2026-10-01', 10);
        $this->record(AttendanceStatus::Late->value, '2026-10-01', 5);
        $this->record(AttendanceStatus::Late->value, '2026-10-03', 4);

        // Autre école : ne doit jamais apparaître.
        $other = School::factory()->create();
        $otherClass = ClassRoom::factory()->create(['school_id' => $other->id]);
        $otherStudent = Student::factory()->create(['school_id' => $other->id, 'class_id' => $otherClass->id]);
        AttendanceRecord::withoutGlobalScopes()->create([
            'school_id' => $other->id,
            'student_id' => $otherStudent->id,
            'class_room_id' => $otherClass->id,
            'attendance_date' => '2026-10-01',
            'status' => AttendanceStatus::Absent->value,
        ]);

        $evolution = app(BuildAttendanceEvolution::class)->handle('custom', '2026-10-01', '2026-10-04');

        $this->assertSame('day', $evolution['unit']);
        $this->assertCount(4, $evolution['buckets']);
        $this->assertSame(1, $evolution['buckets'][0]['absences']);
        $this->assertSame(2, $evolution['buckets'][0]['retards']);
        $this->assertSame(0, $evolution['buckets'][1]['absences']);
        $this->assertSame(1, $evolution['buckets'][2]['retards']);
        $this->assertSame(
            ['absences' => 1, 'retards' => 3, 'delay_minutes' => 19],
            $evolution['totals'],
        );
    }

    public function test_the_day_granularity_defaults_to_a_fortnight(): void
    {
        $admin = User::factory()->admin()->forSchool($this->school)->create();
        $this->actingAs($admin);

        $evolution = app(BuildAttendanceEvolution::class)->handle('day');

        $this->assertCount(14, $evolution['buckets']);
        $this->assertSame('day', $evolution['unit']);
    }

    public function test_the_attendance_page_exposes_the_evolution_payload(): void
    {
        $admin = User::factory()->admin()->forSchool($this->school)->create();
        $this->record('absent', now()->toDateString());

        $this->actingAs($admin)
            ->get(route('admin.attendance.index', ['granularity' => 'day']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Attendance/Index')
                ->where('routePrefix', 'admin')
                ->has('evolution')
                ->where('evolution.granularity', 'day')
                ->has('evolution.buckets', 14)
                ->where('initialTab', 'liste'));
    }

    public function test_the_dashboard_shows_the_ten_latest_delays_and_absences(): void
    {
        $admin = User::factory()->admin()->forSchool($this->school)->create();

        // Dix retards, du plus récent au plus ancien, plus deux absences
        // plus anciennes : le widget ne garde que les dix plus récents.
        foreach (range(1, 10) as $day) {
            $this->record('late', now()->subDays($day)->toDateString(), $day);
        }
        $this->record('absent', now()->subDays(20)->toDateString());
        $this->record('absent', now()->subDays(21)->toDateString());

        // Un élève présent ne doit jamais apparaître dans le widget.
        $this->record('present', now()->toDateString());

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('routePrefix', 'admin')
                ->has('recentIncidents.items', 10)
                ->where('recentIncidents.late_count', 10)
                ->where('recentIncidents.absent_count', 0)
                ->where('recentIncidents.items.0.status', 'late')
                ->where('recentIncidents.items.0.delay_minutes', 1)
                ->where('recentIncidents.items.9.delay_minutes', 10));
    }

    public function test_the_surveillant_reaches_school_life_but_not_administration(): void
    {
        $surveillant = User::factory()->surveillant()->forSchool($this->school)->create();

        $this->actingAs($surveillant)
            ->get(route('surveillant.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('routePrefix', 'surveillant')
                ->has('timetableBrief')
                ->has('recentIncidents'));

        $this->actingAs($surveillant)
            ->get(route('surveillant.attendance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Attendance/Index')
                ->where('routePrefix', 'surveillant'));

        $this->actingAs($surveillant)
            ->get(route('surveillant.timetables.index'))
            ->assertOk();

        $this->actingAs($surveillant)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_an_admin_can_assign_the_surveillant_role(): void
    {
        $admin = User::factory()->admin()->forSchool($this->school)->create();

        $this->actingAs($admin)
            ->markSensitiveTwoFactorVerified('manage_users')
            ->post(route('admin.staff.store'), [
                'first_name' => 'Paul',
                'last_name' => 'Awono',
                'email' => 'paul.awono@ecole.test',
                'role' => Role::Surveillant->value,
                'is_active' => true,
                'password' => 'motdepasse123',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'school_id' => $this->school->id,
            'role' => Role::Surveillant->value,
        ]);
    }
}
