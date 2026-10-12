<?php

namespace Tests\Feature\Platform;

use App\Enums\Role;
use App\Models\Room;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RoomImportTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): User
    {
        $admin = User::factory()->withoutSchool()->create([
            'role' => Role::PlatformAdmin,
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()
            ->createWithContent(
                'rooms.csv',
                "name,building,capacity\n{$content}"
            );
    }

    public function test_a_platform_admin_can_import_rooms_for_a_school(): void
    {
        $school = School::factory()->create();
        $this->platformAdmin();

        $file = $this->csv(
            "Salle 1,A,40\nSalle 2,A,35\nLaboratoire Physique,B,25"
        );

        $this->post(
            route('platform.imports.store', $school),
            ['type' => 'rooms', 'file' => $file]
        )->assertSessionHasNoErrors();

        $this->assertSame(3, Room::where('school_id', $school->id)->count());
        $this->assertDatabaseHas('rooms', [
            'school_id' => $school->id,
            'name' => 'Laboratoire Physique',
            'building' => 'B',
            'capacity' => 25,
        ]);
    }

    public function test_room_import_updates_an_existing_room(): void
    {
        $school = School::factory()->create();
        Room::factory()->create([
            'school_id' => $school->id,
            'name' => 'Salle 1',
            'capacity' => 40,
        ]);
        $this->platformAdmin();

        $file = $this->csv('Salle 1,A,50');

        $this->post(
            route('platform.imports.store', $school),
            ['type' => 'rooms', 'file' => $file]
        )->assertSessionHasNoErrors();

        $this->assertSame(1, Room::where('school_id', $school->id)->count());
        $this->assertDatabaseHas('rooms', [
            'school_id' => $school->id,
            'name' => 'Salle 1',
            'capacity' => 50,
        ]);
    }

    public function test_room_import_rejects_an_invalid_row(): void
    {
        $school = School::factory()->create();
        $this->platformAdmin();

        // Ligne avec un nom manquant : rejetée par la validation.
        $file = $this->csv("Salle 1,A,40\n,B,25");

        $this->post(
            route('platform.imports.store', $school),
            ['type' => 'rooms', 'file' => $file]
        )->assertSessionHasNoErrors();

        $this->assertSame(1, Room::where('school_id', $school->id)->count());
    }
}