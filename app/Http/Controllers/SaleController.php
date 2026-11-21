<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Services\Sales\SaleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService
    ) {}

    public function index(Request $request): View
    {
        $request->validate([
            'period' => ['nullable', 'in:daily,monthly,yearly'],
            'year' => ['nullable', 'integer', 'between:2000,'.now()->year],
        ]);

        $period = $request->query('period', 'daily');
        $year = (int) $request->query('year', now()->year);

        [$start, $end] = match ($period) {
            'monthly' => [Carbon::create($year, 1, 1)->startOfDay(), Carbon::create($year, 12, 31)->endOfDay()],
            'yearly' => [Carbon::create(max(2000, $year - 4), 1, 1)->startOfDay(), Carbon::create($year, 12, 31)->endOfDay()],
            default => [
                ($year === now()->year ? now()->copy()->startOfDay() : Carbon::create($year, 12, 31)->startOfDay())->subDays(29),
                $year === now()->year ? now()->copy()->endOfDay() : Carbon::create($year, 12, 31)->endOfDay(),
            ],
        };

        $groupedSales = Sale::query()
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('sale_date, SUM(quantity) as quantity, SUM(total_cost) as cost, SUM(total_revenue) as revenue, SUM(profit) as profit')
            ->groupBy('sale_date')
            ->get()
            ->keyBy(fn (Sale $sale) => $sale->sale_date->toDateString());

        $buckets = match ($period) {
            'monthly' => collect(range(1, 12))->map(fn (int $month) => [
                'key' => Carbon::create($year, $month, 1)->format('Y-m'),
                'label' => Carbon::create($year, $month, 1)->locale(app()->getLocale())->translatedFormat('M'),
            ]),
            'yearly' => collect(range(max(2000, $year - 4), $year))->map(fn (int $chartYear) => [
                'key' => (string) $chartYear,
                'label' => (string) $chartYear,
            ]),
            default => collect(range(0, $start->diffInDays($end)))->map(fn (int $day) => [
                'key' => $start->copy()->addDays($day)->toDateString(),
                'label' => $start->copy()->addDays($day)->format('d/m'),
            ]),
        };

        $chart = $buckets->map(function (array $bucket) use ($groupedSales, $period) {
            $totals = $groupedSales
                ->filter(function (Sale $sale) use ($bucket, $period) {
                    $date = $sale->sale_date;

                    return match ($period) {
                        'monthly' => $date->format('Y-m') === $bucket['key'],
                        'yearly' => $date->format('Y') === $bucket['key'],
                        default => $date->toDateString() === $bucket['key'],
                    };
                })
                ->reduce(fn (array $carry, Sale $sale) => [
                    'quantity' => $carry['quantity'] + (int) $sale->quantity,
                    'cost' => $carry['cost'] + (float) $sale->cost,
                    'revenue' => $carry['revenue'] + (float) $sale->revenue,
                    'profit' => $carry['profit'] + (float) $sale->profit,
                ], ['quantity' => 0, 'cost' => 0, 'revenue' => 0, 'profit' => 0]);

            return [...$bucket, ...$totals];
        });

        $summary = $chart->reduce(fn (array $carry, array $bucket) => [
            'quantity' => $carry['quantity'] + $bucket['quantity'],
            'cost' => $carry['cost'] + $bucket['cost'],
            'revenue' => $carry['revenue'] + $bucket['revenue'],
            'profit' => $carry['profit'] + $bucket['profit'],
        ], ['quantity' => 0, 'cost' => 0, 'revenue' => 0, 'profit' => 0]);
        $summary['sales_count'] = Sale::query()
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->count();
        $summary['profit_percentage'] = $summary['cost'] > 0
            ? ($summary['profit'] / $summary['cost']) * 100
            : null;

        $sales = Sale::query()
            ->with(['warehouse:id,name', 'product:id,unit'])
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->latest('sale_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $products = Product::query()
            ->where('is_active', true)
            ->with('stocks')
            ->orderBy('name')
            ->get();
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $years = range(now()->year, 2000);

        return view('sales.index', compact(
            'period',
            'year',
            'years',
            'start',
            'end',
            'chart',
            'summary',
            'sales',
            'products',
            'warehouses',
        ));
    }

    public function store(StoreSaleRequest $request)
    {
        $sale = $this->saleService->createSale($request->validated(), $request->user());

        return redirect()
            ->route('sales.index', ['period' => 'monthly', 'year' => $sale->sale_date->year])
            ->with('success', __('Sale recorded and stock updated successfully.'));
    }
}
