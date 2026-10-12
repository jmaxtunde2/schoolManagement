<?php

namespace Tests\Feature\Timetable;

use App\Actions\Timetable\BuildTimetableBrief;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Aperçu de l'exécution de l'emploi du temps affiché sur les tableaux de bord.
 */
class TimetableBriefTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private ClassRoom $classRoom;

    private Subject $subject;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->year = AcademicYear::factory()->forSchool($this->school)->create(['is_current' => true]);
        $this->classRoom = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => '6ème A',
        ]);
        $this->subject = Subject::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'Mathématiques',
        ]);
        $this->admin = User::factory()->admin()->forSchool($this->school)->create();

        $this->actingAs($this->admin);
    }

    private function slot(int $day, string $startsAt, string $endsAt): Timetable
    {
        return Timetable::factory()->forSchool($this->school)->create([
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => $day,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    public function test_it_splits_slots_between_morning_and_evening_and_marks_the_current_one(): void
    {
        // 2026-10-05 est un lundi.
        $morning = $this->slot(1, '08:00', '09:55');
        $this->slot(1, '10:30', '11:55');
        $this->slot(1, '14:00', '15:55');

        // 10:00 : le cours de 08:00–09:55 est terminé, celui de 10:30 est à venir.
        $brief = app(BuildTimetableBrief::class)->handle(Carbon::parse('2026-10-05 10:00:00'));

        $this->assertTrue($brief['is_school_day']);
        $this->assertSame('Lundi', $brief['day_label']);
        $this->assertSame(3, $brief['total']);
        $this->assertSame(2, $brief['morning']['count']);
        $this->assertSame(1, $brief['evening']['count']);
        $this->assertSame(0, $brief['in_progress_count']);
        $this->assertSame('past', $brief['morning']['slots'][0]['status']);
        $this->assertSame('upcoming', $brief['morning']['slots'][1]['status']);
        $this->assertSame('upcoming', $brief['evening']['slots'][0]['status']);

        // 08:30 : le cours du matin est en cours.
        $brief = app(BuildTimetableBrief::class)->handle(Carbon::parse('2026-10-05 08:30:00'));

        $this->assertSame(1, $brief['in_progress_count']);
        $this->assertSame(1, $brief['morning']['in_progress_count']);
        $this->assertSame('current', $brief['morning']['slots'][0]['status']);
        $this->assertSame($morning->id, $brief['morning']['slots'][0]['id']);
    }

    public function test_it_ignores_slots_of_another_day(): void
    {
        $this->slot(2, '08:00', '09:55');

        $brief = app(BuildTimetableBrief::class)->handle(Carbon::parse('2026-10-05 08:30:00'));

        $this->assertSame(0, $brief['total']);
        $this->assertSame(0, $brief['morning']['count']);
        $this->assertSame(0, $brief['evening']['count']);
    }

    public function test_it_reports_a_non_school_day(): void
    {
        $this->slot(1, '08:00', '09:55');

        // 2026-10-11 est un dimanche.
        $brief = app(BuildTimetableBrief::class)->handle(Carbon::parse('2026-10-11 08:30:00'));

        $this->assertFalse($brief['is_school_day']);
        $this->assertSame('Dimanche', $brief['day_label']);
        $this->assertSame(0, $brief['total']);
    }

    public function test_the_admin_dashboard_exposes_the_timetable_brief(): void
    {
        $this->slot(1, '08:00', '09:55');

        Carbon::setTestNow(Carbon::parse('2026-10-05 08:30:00'));

        try {
            $this->get(route('admin.dashboard'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Admin/Dashboard')
                    ->has('timetableBrief')
                    ->where('timetableBrief.is_school_day', true)
                    ->where('timetableBrief.total', 1)
                    ->where('timetableBrief.in_progress_count', 1)
                    ->where('timetableBrief.morning.slots.0.subject_name', 'Mathématiques')
                    ->where('timetableBrief.morning.slots.0.class_name', '6ème A')
                );
        } finally {
            Carbon::setTestNow();
        }
    }
}
