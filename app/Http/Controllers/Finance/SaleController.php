<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\BuildMoneyEvolution;
use App\Enums\SaleCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SaleRequest;
use App\Models\Sale;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ventes de l'établissement (uniformes, manuels, fournitures…).
 *
 * Saisie par l'administrateur ou la secrétaire, consultation + statistiques pour
 * les deux, suppression réservée à l'administrateur.
 */
class SaleController extends Controller
{
    public function index(Request $request, BuildMoneyEvolution $evolution): Response
    {
        $user = $request->user();

        $filters = $request->validate([
            'category' => ['nullable', Rule::enum(SaleCategory::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:120'],
            'tab' => ['nullable', Rule::in(['liste', 'evolution'])],
        ]);

        $query = Sale::query()
            ->with(['creator:id,name'])
            ->when(! empty($filters['category']), fn ($q) => $q->where('category', $filters['category']))
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('sold_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($q) => $q->whereDate('sold_at', '<=', $filters['date_to']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(fn ($search) => $search->where('item', 'like', $term)->orWhere('buyer', 'like', $term));
            });

        $sales = $query->latest('sold_at')->latest('id')->paginate(20)->withQueryString()
            ->through(fn (Sale $sale) => $this->present($sale));

        $monthStart = now()->startOfMonth()->toDateString();
        $monthAgg = Sale::query()->whereDate('sold_at', '>=', $monthStart);

        return Inertia::render('Admin/Sales/Index', [
            'sales' => $sales,
            'categories' => collect(SaleCategory::cases())->map(fn (SaleCategory $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ])->values(),
            'filters' => $filters,
            'routePrefix' => $this->routePrefix($request),
            'initialTab' => $filters['tab'] ?? 'liste',
            'can_manage' => $user->isAdmin(),
            'stats' => [
                'total_revenue' => (int) Sale::query()->sum('total'),
                'month_revenue' => (int) (clone $monthAgg)->sum('total'),
                'month_count' => (clone $monthAgg)->sum('quantity'),
                'total_count' => Sale::query()->sum('quantity'),
            ],
            'evolution' => $evolution->handle('sale'),
        ]);
    }

    public function store(SaleRequest $request, AuditService $audit): RedirectResponse
    {
        $total = (int) $request->validated('quantity') * (int) $request->validated('unit_price');

        $sale = Sale::create([
            'school_id' => $request->user()->school_id,
            'category' => $request->validated('category'),
            'item' => $request->validated('item'),
            'quantity' => $request->validated('quantity'),
            'unit_price' => $request->validated('unit_price'),
            'total' => $total,
            'sold_at' => $request->validated('sold_at'),
            'buyer' => $request->validated('buyer'),
            'created_by' => $request->user()->id,
        ]);

        $audit->log('sale.created', $sale, ['total' => $total, 'category' => $sale->category->value]);

        return back()->with('success', 'Vente enregistrée.');
    }

    public function destroy(Request $request, Sale $sale, AuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless((int) $sale->school_id === (int) $request->user()->school_id, 403);

        $sale->delete();
        $audit->log('sale.deleted', $sale, ['total' => $sale->total]);

        return back()->with('success', 'Vente supprimée.');
    }

    private function present(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'category' => $sale->category->value,
            'category_label' => $sale->category->label(),
            'item' => $sale->item,
            'quantity' => $sale->quantity,
            'unit_price' => $sale->unit_price,
            'total' => $sale->total,
            'sold_at' => $sale->sold_at->format('Y-m-d'),
            'buyer' => $sale->buyer,
            'creator_name' => $sale->creator?->name,
        ];
    }

    private function routePrefix(Request $request): string
    {
        return $request->user()->isAdmin() ? 'admin' : 'secretary';
    }
}