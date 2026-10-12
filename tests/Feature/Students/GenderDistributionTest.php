<?php

namespace Tests\Feature\Students;

use App\Actions\Students\BuildGenderDistribution;
use App\Enums\Role;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Répartition filles / garçons par classe (action, page Classes, tableaux de bord).
 */
class GenderDistributionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private ClassRoom $classA;

    private ClassRoom $classB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->classA = ClassRoom::factory()->create(['school_id' => $this->school->id, 'name' => '6ème A']);
        $this->classB = ClassRoom::factory()->create(['school_id' => $this->school->id, 'name' => '6ème B']);

        $this->actingAs(User::factory()->admin()->forSchool($this->school)->create());
    }

    public function test_the_action_splits_active_students_by_gender_and_class(): void
    {
        $this->student($this->classA, 'F');
        $this->student($this->classA, 'F');
        $this->student($this->classA, 'M');
        $this->student($this->classA, 'F', isActive: false);

        $this->student($this->classB, 'M');
        $this->student($this->classB, 'M');
        $this->student($this->classB, 'M');
        $this->student($this->classB, null);

        $this->student(null, 'F');

        $distribution = app(BuildGenderDistribution::class)->handle();

        $this->assertSame(8, $distribution['totals']['students']);
        $this->assertSame(3, $distribution['totals']['female']);
        $this->assertSame(4, $distribution['totals']['male']);
        $this->assertSame(1, $distribution['totals']['unspecified']);

        $this->assertSame('6ème A', $distribution['classes'][0]['name']);
        $this->assertSame(3, $distribution['classes'][0]['total']);
        $this->assertSame(2, $distribution['classes'][0]['female']);
        $this->assertSame(1, $distribution['classes'][0]['male']);
        $this->assertSame(67, $distribution['classes'][0]['female_rate']);

        $this->assertSame('6ème B', $distribution['classes'][1]['name']);
        $this->assertSame(4, $distribution['classes'][1]['total']);
        $this->assertSame(3, $distribution['classes'][1]['male']);
        $this->assertSame(1, $distribution['classes'][1]['unspecified']);
        $this->assertSame(75, $distribution['classes'][1]['male_rate']);

        $this->assertSame('unassigned', $distribution['classes'][2]['key']);
        $this->assertSame(1, $distribution['classes'][2]['female']);
        $this->assertSame(100, $distribution['classes'][2]['female_rate']);
    }

    public function test_the_action_ignores_other_schools(): void
    {
        $this->student($this->classA, 'F');

        $otherSchool = School::factory()->create();
        Student::factory()->create([
            'school_id' => $otherSchool->id,
            'class_id' => null,
            'gender' => 'M',
        ]);

        $distribution = app(BuildGenderDistribution::class)->handle();

        $this->assertSame(1, $distribution['totals']['students']);
        $this->assertSame(1, $distribution['totals']['female']);
        $this->assertSame(0, $distribution['totals']['male']);
    }

    public function test_the_classes_page_exposes_the_distribution(): void
    {
        $this->student($this->classA, 'F');
        $this->student($this->classB, 'M');

        $this->get(route('admin.classes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Classes/Index')
                ->has('genderDistribution.classes')
                ->where('genderDistribution.totals.students', 2)
                ->where('genderDistribution.totals.female', 1)
                ->where('genderDistribution.totals.male', 1));
    }

    public function test_the_staff_dashboards_expose_the_distribution(): void
    {
        $this->student($this->classA, 'F');
        $this->student($this->classB, 'M');

        foreach (['admin.dashboard', 'censeur.dashboard', 'surveillant.dashboard'] as $route) {
            $this->actingAs(User::factory()->forSchool($this->school)->create([
                'role' => match ($route) {
                    'admin.dashboard' => Role::Admin,
                    'censeur.dashboard' => Role::Censeur,
                    default => Role::Surveillant,
                },
            ]))
                ->get(route($route))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('genderDistribution.totals.students', 2));
        }
    }

    public function test_the_secretary_and_management_dashboards_expose_the_distribution(): void
    {
        $this->student($this->classA, 'F');
        $this->student($this->classB, 'M');

        $this->actingAs(User::factory()->secretary()->forSchool($this->school)->create())
            ->get(route('secretary.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Secretary/Dashboard')
                ->where('genderDistribution.totals.students', 2));

        $this->actingAs(User::factory()->forSchool($this->school)->create([
            'role' => Role::Director,
        ]))
            ->get(route('director.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/ManagementDashboard')
                ->where('genderDistribution.totals.students', 2));
    }

    private function student(?ClassRoom $classRoom, ?string $gender, bool $isActive = true): Student
    {
        return Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $classRoom?->id,
            'gender' => $gender,
            'is_active' => $isActive,
        ]);
    }
}
