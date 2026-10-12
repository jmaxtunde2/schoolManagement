<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\BuildMoneyEvolution;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SalarySheetRequest;
use App\Models\Expense;
use App\Models\SalaryRecord;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Feuille de paie mensuelle.
 *
 * On choisit un mois : tous les membres du personnel (enseignants, secrétaires,
 * gardiens, agents d'entretien…) s'affichent avec leur salaire déjà saisi pour ce
 * mois (ou vide), et la saisie se fait en une seule fois pour tous. La feuille est
 * matérialisée par une dépense de catégorie `salaire` soumise au même seuil
 * d'approbation que les autres dépenses.
 */
class SalaryController extends Controller
{
    public function index(Request $request, BuildMoneyEvolution $evolution): Response
    {
        $month = $this->month($request->input('month'));

        $staff = $this->roster($request);
        $existing = SalaryRecord::query()
            ->whereDate('salary_month', $month)
            ->get()
            ->keyBy('staff_id');

        $salaries = $staff->map(fn (array $member) => [
            ...$member,
            'amount' => isset($existing[$member['id']]) ? (int) $existing[$member['id']]->amount : null,
        ]);

        $history = Expense::query()
            ->where('category', ExpenseCategory::Salaire->value)
            ->withCount('salaryRecords')
            ->latest('expense_date')
            ->get()
            ->map(fn (Expense $expense) => [
                'id' => $expense->id,
                'reference' => $expense->reference,
                'month' => $expense->expense_date->format('Y-m'),
                'month_label' => $this->monthLabel($expense->expense_date),
                'amount' => $expense->amount,
                'staff_count' => $expense->salary_records_count,
                'status' => $expense->status->value,
                'status_label' => $expense->status->label(),
                'approved_at' => $expense->approved_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Salaries/Index', [
            'month' => $month->format('Y-m'),
            'salaries' => $salaries,
            'history' => $history,
            'routePrefix' => $this->routePrefix($request),
            'can_manage' => $request->user()->isAdmin(),
            'evolution' => $evolution->handle('salary'),
        ]);
    }

    public function save(SalarySheetRequest $request, AuditService $audit): RedirectResponse
    {
        $user = $request->user();
        $school = $user->school;
        $month = Carbon::createFromFormat('Y-m', $request->validated('month'))->startOfMonth();

        $expense = DB::transaction(function () use ($request, $user, $school, $month, $audit) {
            $lines = collect($request->validated('lines'))->filter(fn (array $line) => ! empty($line['amount']));
            $total = $lines->sum(fn (array $line) => (int) $line['amount']);

            $expense = Expense::query()
                ->where('category', ExpenseCategory::Salaire->value)
                ->whereDate('expense_date', $month)
                ->first();

            if ($expense) {
                $expense->forceFill([
                    'amount' => $total,
                    'status' => $expense->status === ExpenseStatus::Approved
                        ? ExpenseStatus::Approved
                        : $this->statusFor($school, $total),
                ])->save();
            } else {
                $expense = Expense::create([
                    'school_id' => $user->school_id,
                    'category' => ExpenseCategory::Salaire->value,
                    'label' => 'Salaires de '.$this->monthLabel($month),
                    'reference' => 'SAL-'.$month->format('Y').'-'.$month->format('m'),
                    'amount' => $total,
                    'expense_date' => $month->toDateString(),
                    'status' => $this->statusFor($school, $total),
                    'created_by' => $user->id,
                ]);
            }

            foreach ($lines as $line) {
                SalaryRecord::query()->updateOrCreate(
                    [
                        'school_id' => $user->school_id,
                        'staff_id' => $line['staff_id'],
                        'salary_month' => $month->toDateString(),
                    ],
                    ['expense_id' => $expense->id, 'amount' => (int) $line['amount']]
                )->forceFill(['updated_by' => $user->id])->save();
            }

            SalaryRecord::query()
                ->where('school_id', $user->school_id)
                ->whereDate('salary_month', $month)
                ->where('expense_id', $expense->id)
                ->whereNotIn('staff_id', $lines->pluck('staff_id'))
                ->delete();

            $expense->forceFill(['amount' => (int) $expense->salaryRecords()->sum('amount')])->save();

            $audit->log('salary.saved', $expense, [
                'month' => $month->format('Y-m'),
                'amount' => $expense->amount,
                'status' => $expense->status->value,
            ]);

            return $expense;
        });

        return redirect()
            ->route($this->routePrefix($request).'.salaries.index', ['month' => $month->format('Y-m')])
            ->with('success', $expense->status === ExpenseStatus::Pending
                ? 'Paie du mois enregistrée : elle doit être approuvée par l\'administrateur.'
                : 'Paie du mois enregistrée.');
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function roster(Request $request)
    {
        return $request->user()->school->staff()
            ->get(['id', 'name', 'role'])
            ->filter(fn ($member) => $member->role->isSchoolStaff())
            ->sortBy(fn ($member) => $member->name)
            ->values()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->role->value,
                'role_label' => $member->role->label(),
            ]);
    }

    private function statusFor($school, int $total): ExpenseStatus
    {
        return $total > $this->threshold($school) ? ExpenseStatus::Pending : ExpenseStatus::Approved;
    }

    private function threshold($school): int
    {
        $options = $school->settingsOrNew()->options ?? [];

        return (int) ($options['expense_approval_threshold'] ?? 50000);
    }

    private function month(?string $value): Carbon
    {
        $month = blank($value) ? null : Carbon::createFromFormat('Y-m', $value);

        return $month?->startOfMonth() ?? now()->startOfMonth();
    }

    private function monthLabel(Carbon $date): string
    {
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin',
            7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
        ];

        return $months[$date->month].' '.$date->format('Y');
    }

    private function routePrefix(Request $request): string
    {
        return $request->user()->isAdmin() ? 'admin' : 'secretary';
    }
}