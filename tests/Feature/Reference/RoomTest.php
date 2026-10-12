<?php

namespace Tests\Feature\Reference;

use App\Models\Room;
use App\Models\School;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    private function admin(School $school): User
    {
        return User::factory()->admin()->forSchool($school)->create();
    }

    public function test_admin_can_create_update_and_delete_a_room(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin($school))
            ->post('/admin/rooms', ['name' => 'Laboratoire Physique'])
            ->assertSessionHasNoErrors();

        $room = Room::first();
        $this->assertSame('Laboratoire Physique', $room->name);
        $this->assertSame($school->id, $room->school_id);

        $this->actingAs($this->admin($school))
            ->put("/admin/rooms/{$room->id}", [
                'name' => 'Labo Physique',
                'building' => 'B',
                'capacity' => 30,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Labo Physique', $room->fresh()->name);
        $this->assertSame(30, $room->fresh()->capacity);

        $this->actingAs($this->admin($school))
            ->delete("/admin/rooms/{$room->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_room_name_must_be_unique_within_the_same_school_only(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        Room::factory()->create(['school_id' => $schoolA->id, 'name' => 'Salle 1']);

        $this->actingAs($this->admin($schoolA))
            ->post('/admin/rooms', ['name' => 'Salle 1'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin($schoolB))
            ->post('/admin/rooms', ['name' => 'Salle 1'])
            ->assertSessionHasNoErrors();
    }

    public function test_deleting_a_room_releases_the_timetable_slots(): void
    {
        $school = School::factory()->create();
        $room = Room::factory()->create(['school_id' => $school->id]);
        $slot = Timetable::factory()->forSchool($school)->create(['room_id' => $room->id]);

        $this->actingAs($this->admin($school))
            ->delete("/admin/rooms/{$room->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('timetables', ['id' => $slot->id, 'room_id' => null]);
    }

    public function test_a_room_of_another_school_cannot_be_referenced_in_a_timetable(): void
    {
        $school = School::factory()->create();
        $foreignRoom = Room::factory()->create();
        $admin = $this->admin($school);

        $year = \App\Models\AcademicYear::factory()->forSchool($school)->create();
        $class = \App\Models\ClassRoom::factory()->create(['school_id' => $school->id]);
        $subject = \App\Models\Subject::factory()->create(['school_id' => $school->id]);

        $this->actingAs($admin)->post('/admin/timetables', [
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => null,
            'room_id' => $foreignRoom->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:55',
        ])->assertSessionHasErrors('room_id');
    }

    public function test_the_admin_rooms_page_lists_the_rooms(): void
    {
        $school = School::factory()->create();
        Room::factory()->create(['school_id' => $school->id, 'name' => 'Salle 1']);
        Room::factory()->create(['school_id' => $school->id, 'name' => 'Salle 2']);

        $this->actingAs($this->admin($school))
            ->get('/admin/rooms')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Rooms/Index')
                ->has('rooms', 2)
                ->where('rooms.0.name', 'Salle 1')
            );
    }
}