<?php

namespace Tests\Feature\Reference;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AcademicPeriodTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->year = AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'name' => '2026-2027',
            'is_current' => true,
        ]);
    }

    private function admin(?School $school = null): User
    {
        return User::factory()->admin()->forSchool($school ?? $this->school)->create();
    }

    public function test_admin_can_create_a_period_for_an_academic_year(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/academic-periods', [
                'academic_year_id' => $this->year->id,
                'name' => 'Trimestre 1',
                'position' => 1,
                'starts_at' => '2026-09-01',
                'ends_at' => '2026-12-15',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('academic_periods', [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'is_closed' => false,
        ]);
    }

    public function test_admin_can_update_a_period(): void
    {
        $period = AcademicPeriod::factory()->forYear($this->year)->create([
            'name' => 'Trimestre 1',
            'position' => 1,
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/academic-periods/{$period->id}", [
                'academic_year_id' => $this->year->id,
                'name' => 'Semestre 1',
                'position' => 2,
                'is_closed' => true,
            ])
            ->assertSessionHasNoErrors();

        $period->refresh();

        $this->assertSame('Semestre 1', $period->name);
        $this->assertSame(2, $period->position);
        $this->assertTrue($period->is_closed);
    }

    public function test_admin_can_close_and_reopen_a_period(): void
    {
        $period = AcademicPeriod::factory()->forYear($this->year)->create([
            'is_closed' => false,
        ]);

        $this->actingAs($this->admin())
            ->post("/admin/academic-periods/{$period->id}/toggle")
            ->assertSessionHasNoErrors();

        $this->assertTrue($period->fresh()->is_closed);

        $this->actingAs($this->admin())
            ->post("/admin/academic-periods/{$period->id}/toggle")
            ->assertSessionHasNoErrors();

        $this->assertFalse($period->fresh()->is_closed);
    }

    public function test_admin_can_delete_an_unused_period(): void
    {
        $period = AcademicPeriod::factory()->forYear($this->year)->create();

        $this->actingAs($this->admin())
            ->delete("/admin/academic-periods/{$period->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('academic_periods', ['id' => $period->id]);
    }

    public function test_position_must_be_unique_within_an_academic_year(): void
    {
        AcademicPeriod::factory()->forYear($this->year)->create([
            'name' => 'Trimestre 1',
            'position' => 1,
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/academic-periods', [
                'academic_year_id' => $this->year->id,
                'name' => 'Trimestre 2',
                'position' => 1,
            ])
            ->assertSessionHasErrors('position');
    }

    public function test_a_period_can_be_updated_without_triggering_its_own_position_check(): void
    {
        $period = AcademicPeriod::factory()->forYear($this->year)->create([
            'name' => 'Trimestre 1',
            'position' => 1,
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/academic-periods/{$period->id}", [
                'academic_year_id' => $this->year->id,
                'name' => 'Trimestre 1',
                'position' => 1,
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_end_date_must_not_precede_start_date(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/academic-periods', [
                'academic_year_id' => $this->year->id,
                'name' => 'Trimestre 1',
                'position' => 1,
                'starts_at' => '2026-12-15',
                'ends_at' => '2026-09-01',
            ])
            ->assertSessionHasErrors('ends_at');
    }

    public function test_a_used_period_cannot_be_deleted(): void
    {
        $period = AcademicPeriod::factory()->forYear($this->year)->create();
        $student = Student::factory()->create([
            'school_id' => $this->school->id,
        ]);

        AttendanceRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'class_room_id' => null,
            'academic_period_id' => $period->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        $this->actingAs($this->admin())
            ->delete("/admin/academic-periods/{$period->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('academic_periods', ['id' => $period->id]);
    }

    public function test_teacher_cannot_manage_periods(): void
    {
        $this->actingAs(
            User::factory()->teacher()->forSchool($this->school)->create()
        )
            ->get('/admin/academic-periods')
            ->assertForbidden();
    }

    public function test_period_of_another_school_is_not_editable(): void
    {
        $otherYear = AcademicYear::factory()->create();
        $foreignPeriod = AcademicPeriod::factory()->forYear($otherYear)->create([
            'name' => 'Période étrangère',
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/academic-periods/{$foreignPeriod->id}", [
                'academic_year_id' => $otherYear->id,
                'name' => 'Détournée',
                'position' => 1,
            ])
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->delete("/admin/academic-periods/{$foreignPeriod->id}")
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->post("/admin/academic-periods/{$foreignPeriod->id}/toggle")
            ->assertNotFound();

        $this->assertSame('Période étrangère', $foreignPeriod->fresh()->name);
        $this->assertFalse($foreignPeriod->fresh()->is_closed);
    }

    public function test_a_period_cannot_be_created_against_a_foreign_academic_year(): void
    {
        $otherYear = AcademicYear::factory()->create();

        $this->actingAs($this->admin())
            ->post('/admin/academic-periods', [
                'academic_year_id' => $otherYear->id,
                'name' => 'Période étrangère',
                'position' => 1,
            ])
            ->assertSessionHasErrors('academic_year_id');
    }

    public function test_index_lists_periods_grouped_by_academic_year_with_usage(): void
    {
        $period = AcademicPeriod::factory()->forYear($this->year)->create([
            'name' => 'Trimestre 1',
            'position' => 1,
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/academic-periods')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AcademicPeriods/Index')
                ->has('years', 1)
                ->has('periods', 1)
                ->where('periods.0.id', $period->id)
                ->where('periods.0.academic_year', '2026-2027')
                ->where('periods.0.is_closed', false)
                ->where('periods.0.is_used', false)
                ->where('periods.0.evaluations_count', 0)
            );
    }

    public function test_closed_period_blocks_report_card_generation(): void
    {
        $period = AcademicPeriod::factory()->forYear($this->year)->closed()->create();

        $class = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
        ]);
        $subject = Subject::factory()->create([
            'school_id' => $this->school->id,
        ]);
        $class->subjects()->sync([
            $subject->id => ['default_coefficient' => 1],
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/academic/report-cards/generate', [
                'class_id' => $class->id,
                'period_id' => $period->id,
            ])
            ->assertSessionHas('error');
    }
}
