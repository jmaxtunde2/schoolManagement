<?php

namespace Tests\Feature\Finance;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\SaleCategory;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Expense;
use App\Models\SalaryRecord;
use App\Models\Sale;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentContribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $academicYear;

    private ClassRoom $classRoom;

    private User $admin;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->academicYear = AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'is_current' => true,
        ]);
        $this->classRoom = ClassRoom::factory()->create(['school_id' => $this->school->id]);
        $this->admin = User::factory()->admin()->forSchool($this->school)->create();
        $this->secretary = User::factory()->secretary()->forSchool($this->school)->create();
    }

    private function actingAsVerified(User $user): static
    {
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $this->withSession([
            'two_factor_verified_at' => now()->timestamp,
        ])->actingAs($user);
    }

    private function createExpense(array $attributes = []): Expense
    {
        return Expense::create(array_merge([
            'school_id' => $this->school->id,
            'category' => ExpenseCategory::Fonctionnelle->value,
            'label' => 'Facture d\'électricité',
            'amount' => 10000,
            'expense_date' => now()->toDateString(),
            'status' => ExpenseStatus::Approved,
            'created_by' => $this->secretary->id,
        ], $attributes));
    }

    public function test_secretary_creates_expense_below_threshold_and_it_is_auto_approved(): void
    {
        $this->actingAsVerified($this->secretary)
            ->get('/secretary/depenses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Expenses/Index')
                ->where('routePrefix', 'secretary')
                ->where('can_manage', false)
                ->where('threshold', 50000));

        $this->actingAsVerified($this->secretary)
            ->post('/secretary/depenses', [
                'category' => ExpenseCategory::Fonctionnelle->value,
                'label' => 'Réparation de la porte',
                'amount' => 20000,
                'expense_date' => now()->toDateString(),
                'justification' => 'Serrure cassée.',
            ])
            ->assertSessionHasNoErrors();

        $expense = Expense::firstOrFail();
        $this->assertSame(ExpenseStatus::Approved, $expense->status);
        $this->assertSame('DEP-'.str_pad((string) $expense->id, 4, '0', STR_PAD_LEFT), $expense->reference);
        $this->assertSame($this->secretary->id, $expense->created_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'expense.created']);
    }

    public function test_expense_above_threshold_is_pending_until_admin_approves_and_category_salaire_is_rejected(): void
    {
        $this->actingAsVerified($this->secretary)
            ->post('/secretary/depenses', [
                'category' => ExpenseCategory::Materiel->value,
                'label' => 'Ordinateur portable',
                'amount' => 250000,
                'expense_date' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $expense = Expense::firstOrFail();
        $this->assertSame(ExpenseStatus::Pending, $expense->status);

        $this->actingAsVerified($this->admin)
            ->post("/admin/expenses/{$expense->id}/approve")
            ->assertSessionHasNoErrors();

        $fresh = $expense->fresh();
        $this->assertSame(ExpenseStatus::Approved, $fresh->status);
        $this->assertSame($this->admin->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'expense.approved']);

        $this->actingAsVerified($this->secretary)
            ->post('/secretary/depenses', [
                'category' => 'salaire',
                'label' => 'Faux salaire',
                'amount' => 1000,
                'expense_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('category');

        $this->assertSame(1, Expense::query()->count());
    }

    public function test_secretary_cannot_approve_reject_delete_or_update_settings(): void
    {
        $pending = $this->createExpense([
            'amount' => 60000,
            'status' => ExpenseStatus::Pending,
        ]);

        $this->actingAsVerified($this->secretary)
            ->post("/secretary/expenses/{$pending->id}/approve", [])
            ->assertNotFound();
        $this->actingAsVerified($this->secretary)
            ->post("/secretary/expenses/{$pending->id}/reject", [])
            ->assertNotFound();
        $this->actingAsVerified($this->secretary)
            ->delete("/secretary/expenses/{$pending->id}")
            ->assertNotFound();
        $this->actingAsVerified($this->secretary)
            ->put('/secretary/expenses/settings', ['threshold' => 1000])
            ->assertNotFound();

        $this->assertSame(ExpenseStatus::Pending, $pending->fresh()->status);
    }

    public function test_admin_rejects_pending_expense_and_rejected_are_excluded_from_stats(): void
    {
        $this->createExpense(['amount' => 60000, 'status' => ExpenseStatus::Pending]);

        $this->actingAsVerified($this->admin)
            ->get('/admin/expenses')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Expenses/Index')
                ->where('can_manage', true)
                ->where('stats.pending_count', 1));

        $pending = Expense::firstOrFail();
        $this->actingAsVerified($this->admin)
            ->post("/admin/expenses/{$pending->id}/reject")
            ->assertSessionHasNoErrors();

        $this->assertSame(ExpenseStatus::Rejected, $pending->fresh()->status);
        $this->assertSame($this->admin->id, $pending->fresh()->rejected_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'expense.rejected']);

        $this->actingAsVerified($this->admin)
            ->get('/admin/expenses')
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.pending_count', 0)
                ->where('stats.month_total', 0)
                ->where('expenses.data.0.status', 'rejected'));
    }

    public function test_admin_updates_approval_threshold(): void
    {
        $this->actingAsVerified($this->admin)
            ->put('/admin/expenses/settings', ['threshold' => 100000])
            ->assertSessionHasNoErrors();

        $options = $this->school->settingsOrNew()->options;
        $this->assertSame(100000, $options['expense_approval_threshold']);

        $this->actingAsVerified($this->secretary)
            ->get('/secretary/depenses')
            ->assertInertia(fn (Assert $page) => $page->where('threshold', 100000));
    }

    public function test_salary_sheet_saves_roster_and_creates_a_salary_expense(): void
    {
        $gardien = User::factory()->forSchool($this->school)->create(['role' => 'gardien']);
        $agent = User::factory()->forSchool($this->school)->create(['role' => 'agent_entretien']);
        $month = now()->format('Y-m');

        $this->actingAsVerified($this->secretary)
            ->get('/secretary/salaires')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Salaries/Index')
                ->where('can_manage', false)
                ->has('salaries', 4)
                ->has('evolution.buckets', 12));

        $this->actingAsVerified($this->secretary)
            ->post('/secretary/salaires', [
                'month' => $month,
                'lines' => [
                    ['staff_id' => $this->secretary->id, 'amount' => 75000],
                    ['staff_id' => $this->admin->id, 'amount' => 120000],
                    ['staff_id' => $gardien->id, 'amount' => 45000],
                    ['staff_id' => $agent->id, 'amount' => 40000],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $expense = Expense::where('category', ExpenseCategory::Salaire->value)->firstOrFail();
        $this->assertSame('SAL-'.now()->format('Y-m'), $expense->reference);
        $this->assertSame(280000, $expense->amount);
        $this->assertSame(ExpenseStatus::Pending, $expense->status);
        $this->assertSame(4, $expense->salaryRecords()->count());
        $this->assertSame(75000, SalaryRecord::where('staff_id', $this->secretary->id)->value('amount'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'salary.saved']);

        $this->actingAsVerified($this->secretary)
            ->get('/secretary/salaires')
            ->assertInertia(fn (Assert $page) => $page
                ->where('history.0.month', $month)
                ->where('history.0.staff_count', 4)
                ->where('history.0.amount', 280000));
    }

    public function test_salary_sheet_requires_valid_month_and_school_staff(): void
    {
        $outsider = User::factory()->forSchool(School::factory()->create())->create(['role' => 'teacher']);

        $this->actingAsVerified($this->secretary)
            ->post('/secretary/salaires', [
                'month' => '2026-13',
                'lines' => [],
            ])
            ->assertSessionHasErrors(['month', 'lines']);

        $this->actingAsVerified($this->secretary)
            ->post('/secretary/salaires', [
                'month' => now()->format('Y-m'),
                'lines' => [['staff_id' => $outsider->id, 'amount' => 50000]],
            ])
            ->assertSessionHasErrors('lines.0.staff_id');

        $this->assertSame(0, Expense::count());
        $this->assertSame(0, SalaryRecord::count());
    }

    public function test_sales_are_created_by_secretary_and_deleted_by_admin_only(): void
    {
        $this->actingAsVerified($this->secretary)
            ->get('/secretary/ventes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Sales/Index')
                ->where('can_manage', false)
                ->has('categories', 4));

        $this->actingAsVerified($this->secretary)
            ->post('/secretary/ventes', [
                'category' => SaleCategory::Uniforme->value,
                'item' => 'Uniforme scolaire',
                'quantity' => 3,
                'unit_price' => 15000,
                'sold_at' => now()->toDateString(),
                'buyer' => 'M. Kacou',
            ])
            ->assertSessionHasNoErrors();

        $sale = Sale::firstOrFail();
        $this->assertSame(45000, $sale->total);
        $this->assertSame('Uniforme scolaire', $sale->item);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sale.created']);

        $this->actingAsVerified($this->secretary)
            ->delete("/secretary/ventes/{$sale->id}")
            ->assertNotFound();

        $this->actingAsVerified($this->admin)
            ->delete("/admin/sales/{$sale->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function test_contributions_are_saved_and_stats_and_deletion_are_reported(): void
    {
        $student = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
            'is_active' => true,
        ]);

        $this->actingAsVerified($this->secretary)
            ->post('/secretary/contributions', [
                'student_id' => $student->id,
                'academic_year_id' => $this->academicYear->id,
                'amount_due' => 50000,
                'amount_paid' => 20000,
                'paid_at' => now()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $contribution = StudentContribution::where('student_id', $student->id)->firstOrFail();
        $this->assertSame(50000, $contribution->amount_due);
        $this->assertSame(20000, $contribution->amount_paid);
        $this->assertSame('partial', $contribution->status);
        $this->assertNotNull($contribution->paid_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contribution.saved']);

        $this->actingAsVerified($this->admin)
            ->get('/admin/contributions')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Contributions/Index')
                ->where('can_manage', true)
                ->where('stats.due', 50000)
                ->where('stats.collected', 20000)
                ->where('stats.remaining', 30000)
                ->where('contributions.data.0.status', 'partial'));

        $this->actingAsVerified($this->secretary)
            ->delete("/secretary/contributions/{$contribution->id}")
            ->assertNotFound();

        $this->actingAsVerified($this->admin)
            ->delete("/admin/contributions/{$contribution->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('student_contributions', ['id' => $contribution->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contribution.deleted']);
    }

    public function test_admin_dashboard_includes_recent_finance(): void
    {
        $expense = $this->createExpense();
        $sale = Sale::create([
            'school_id' => $this->school->id,
            'category' => SaleCategory::Uniforme->value,
            'item' => 'Blouse',
            'quantity' => 1,
            'unit_price' => 10000,
            'total' => 10000,
            'sold_at' => now()->toDateString(),
            'created_by' => $this->secretary->id,
        ]);
        Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
            'is_active' => true,
        ]);
        StudentContribution::create([
            'school_id' => $this->school->id,
            'student_id' => Student::firstOrFail()->id,
            'academic_year_id' => $this->academicYear->id,
            'amount_due' => 50000,
            'amount_paid' => 50000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $contribution = StudentContribution::firstOrFail();

        $this->actingAsVerified($this->admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('finance', true)
                ->has('recentExpenses', 1)
                ->where('recentExpenses.0.id', $expense->id)
                ->has('recentSales', 1)
                ->where('recentSales.0.id', $sale->id)
                ->has('recentContributions', 1)
                ->where('recentContributions.0.id', $contribution->id)
                ->where('financeSummary.revenue', 60000)
                ->where('financeSummary.expenses', 10000)
                ->where('financeSummary.profit', 50000)
                ->where('financeSummary.margin', 83));
    }

    public function test_admin_performance_page_reports_all_categories(): void
    {
        $expense = $this->createExpense();
        Sale::create([
            'school_id' => $this->school->id,
            'category' => SaleCategory::Uniforme->value,
            'item' => 'Blouse',
            'quantity' => 2,
            'unit_price' => 10000,
            'total' => 20000,
            'sold_at' => now()->toDateString(),
            'created_by' => $this->secretary->id,
        ]);
        StudentContribution::create([
            'school_id' => $this->school->id,
            'student_id' => Student::factory()->create([
                'school_id' => $this->school->id,
                'class_id' => $this->classRoom->id,
                'is_active' => true,
            ])->id,
            'academic_year_id' => $this->academicYear->id,
            'amount_due' => 50000,
            'amount_paid' => 30000,
            'status' => 'partial',
            'paid_at' => now(),
        ]);

        $this->actingAsVerified($this->admin)
            ->get('/admin/performance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Performance/Index')
                ->has('performance', 5)
                ->has('performance.finance.buckets', 12)
                ->has('performance.finance.series', 3)
                ->has('performance.academique.series', 4)
                ->has('performance.eleves.series', 2)
                ->has('performance.personnel.series', 1)
                ->has('performance.communication.series', 1)
                ->where('performance.finance.summary.0.value', '50 000 F')
                ->where('performance.finance.summary.1.value', '10 000 F')
                ->where('performance.finance.summary.2.value', '+ 40 000 F')
                ->where('performance.finance.summary.3.value', '80 %'));

        $teacher = $this->secretary;
        $teacher->role = 'teacher';
        $teacher->save();
        $this->actingAsVerified($teacher)
            ->get('/admin/performance')
            ->assertForbidden();
    }
}
