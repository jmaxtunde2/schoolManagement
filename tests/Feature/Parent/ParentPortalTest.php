<?php

namespace Tests\Feature\Parent;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\ContributionPayment;
use App\Models\ParentGuardian;
use App\Models\ParentMessage;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentContribution;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    private ClassRoom $classRoom;

    private User $parentUser;

    private ParentGuardian $parent;

    private Student $child;

    private StudentContribution $contribution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->academicYear = AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'is_current' => true,
        ]);
        $this->classRoom = ClassRoom::factory()->create(['school_id' => $this->school->id]);

        $this->parentUser = User::factory()->forSchool($this->school)->create(['role' => Role::Parent]);
        $this->parent = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $this->parentUser->id,
        ]);

        $this->child = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
        ]);
        $this->parent->students()->attach($this->child->id, ['relationship' => 'père']);

        $this->contribution = StudentContribution::create([
            'school_id' => $this->school->id,
            'student_id' => $this->child->id,
            'parent_id' => $this->parent->id,
            'academic_year_id' => $this->academicYear->id,
            'amount_due' => 150000,
            'amount_paid' => 50000,
            'status' => 'partial',
        ]);
    }

    private function actingAsVerified(User $user): static
    {
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $this->withSession([
            'two_factor_verified_at' => now()->timestamp,
        ])->actingAs($user);
    }

    private function makePayment(StudentContribution $contribution, array $attributes = []): ContributionPayment
    {
        return ContributionPayment::create(array_merge([
            'school_id' => $this->school->id,
            'student_contribution_id' => $contribution->id,
            'parent_id' => $contribution->parent_id,
            'amount' => 50000,
            'method' => 'cash',
            'reference' => 'REC-0001',
            'receipt_number' => 'RC-0001',
            'paid_at' => now()->toDateString(),
        ], $attributes));
    }

    public function test_parent_sees_own_children_and_payments_on_finance_page(): void
    {
        $this->makePayment($this->contribution);

        $this->actingAsVerified($this->parentUser)
            ->get('/parent/finance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Finance/Index')
                ->has('payments', 1)
                ->where('payments.0.student', $this->child->full_name)
                ->where('payments.0.amount', 50000)
                ->where('totals.due', 150000)
                ->where('totals.paid', 50000));
    }

    public function test_parent_downloads_statement_and_receipt_as_pdf(): void
    {
        $payment = $this->makePayment($this->contribution);

        $this->actingAsVerified($this->parentUser)
            ->get('/parent/finance/statement')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAsVerified($this->parentUser)
            ->get("/parent/finance/payments/{$payment->id}/receipt")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_parent_cannot_download_another_family_receipt(): void
    {
        $otherParent = ParentGuardian::factory()->create(['school_id' => $this->school->id]);
        $otherChild = Student::factory()->create(['school_id' => $this->school->id]);
        $otherParent->students()->attach($otherChild->id);
        $otherContribution = StudentContribution::create([
            'school_id' => $this->school->id,
            'student_id' => $otherChild->id,
            'parent_id' => $otherParent->id,
            'academic_year_id' => $this->academicYear->id,
            'amount_due' => 100000,
            'amount_paid' => 0,
            'status' => 'pending',
        ]);
        $payment = $this->makePayment($otherContribution);

        $this->actingAsVerified($this->parentUser)
            ->get("/parent/finance/payments/{$payment->id}/receipt")
            ->assertForbidden();
    }

    public function test_parent_message_notifies_admin_censeur_and_secretary_only(): void
    {
        $admin = User::factory()->admin()->forSchool($this->school)->create();
        $censeur = User::factory()->censeur()->forSchool($this->school)->create();
        $secretary = User::factory()->secretary()->forSchool($this->school)->create();
        $teacher = User::factory()->teacher()->forSchool($this->school)->create();

        $this->actingAsVerified($this->parentUser)
            ->post('/parent/messages', [
                'student_id' => $this->child->id,
                'subject' => 'Demande de certificat de scolarité',
                'body' => 'Bonjour, je souhaite obtenir un certificat.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $message = ParentMessage::firstOrFail();
        $this->assertSame('new', $message->status);
        $this->assertSame($this->parent->id, $message->parent_id);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $admin->id, 'type' => 'parent_message.received']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $censeur->id]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $secretary->id]);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $teacher->id]);

        $notification = UserNotification::where('user_id', $admin->id)->firstOrFail();
        $this->assertSame(route('messages.show', $message->id, absolute: false), $notification->data['url']);
    }

    public function test_staff_can_read_parent_message_and_it_is_marked_as_read(): void
    {
        $admin = User::factory()->admin()->forSchool($this->school)->create();

        $this->actingAsVerified($this->parentUser)
            ->post('/parent/messages', [
                'subject' => 'Absence à justifier',
                'body' => 'Mon enfant était malade.',
            ]);

        $message = ParentMessage::firstOrFail();

        $this->actingAsVerified($admin)
            ->get('/messages')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Messages/Index')
                ->has('messages.data', 1));

        $this->actingAsVerified($admin)
            ->get("/messages/{$message->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Messages/Show')
                ->where('message.subject', $message->subject));

        $message->refresh();
        $this->assertSame('read', $message->status);
        $this->assertSame($admin->id, $message->read_by);
        $this->assertNotNull($message->read_at);
    }

    public function test_teacher_cannot_access_staff_parent_messages(): void
    {
        $teacher = User::factory()->teacher()->forSchool($this->school)->create();

        $this->actingAsVerified($teacher)
            ->get('/messages')
            ->assertForbidden();
    }

    public function test_parent_cannot_target_a_student_they_do_not_guard(): void
    {
        $stranger = Student::factory()->create(['school_id' => $this->school->id]);

        $this->actingAsVerified($this->parentUser)
            ->post('/parent/messages', [
                'student_id' => $stranger->id,
                'subject' => 'Sujet',
                'body' => 'Corps du message.',
            ])
            ->assertForbidden();
    }
}
