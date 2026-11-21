<?php

namespace App\Services\Analytics;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Financial picture of the business, combining the invoicing module with
 * the existing direct sales (sales table) and stock data.
 *
 * Revenue  = issued invoices (by issue date) + direct sales revenue.
 * Cash in  = invoice payments (by payment date) + direct sales revenue.
 * Expenses = expenses table (by spent date).
 */
class FinancialAnalytics
{
    public function __construct(private readonly Forecaster $forecaster) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(?CarbonInterface $today = null): array
    {
        $today = Carbon::instance(($today ?? today())->toDateTime())->startOfDay();

        $history = $this->monthlySeries($today, 36);
        $forecast = $this->forecaster->forecast($history['cash_in'], 3);

        return [
            'as_of' => $today->toDateString(),
            'kpis' => $this->kpis($today),
            'trend' => [
                'labels' => array_slice($history['labels'], -12),
                'months' => array_slice($history['months'], -12),
                'revenue' => array_slice($history['revenue'], -12),
                'cash_in' => array_slice($history['cash_in'], -12),
                'expenses' => array_slice($history['expenses'], -12),
            ],
            'forecast' => $forecast + [
                'labels' => collect(range(1, 3))->map(fn ($h) => $today->copy()->startOfMonth()->addMonthsNoOverflow($h)->translatedFormat('M Y'))->all(),
                'history_months' => count(array_filter($history['cash_in'])),
            ],
            'aging' => $this->aging($today),
            'top_customers' => $this->topCustomers(),
            'top_products' => $this->topProducts($today),
            'expense_categories' => $this->expenseCategories($today),
            'payment_behaviour' => $this->paymentBehaviour(),
            'inventory' => $this->inventorySignals(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function kpis(Carbon $today): array
    {
        $start = $today->copy()->startOfMonth();
        $prevStart = $start->copy()->subMonthNoOverflow();
        $prevEnd = $prevStart->copy()->addDays($today->day - 1)->min($prevStart->copy()->endOfMonth());

        $current = [$start->toDateString(), $today->toDateString()];
        $previous = [$prevStart->toDateString(), $prevEnd->toDateString()];

        $revenue = [$this->revenueBetween(...$current), $this->revenueBetween(...$previous)];
        $cash = [$this->cashInBetween(...$current), $this->cashInBetween(...$previous)];
        $expenses = [$this->expensesBetween(...$current), $this->expensesBetween(...$previous)];

        $invoiced = (float) Invoice::query()->issued()->sum('total');
        $outstanding = (float) Invoice::query()->open()->sum('amount_due');

        return [
            'revenue' => $this->metric($revenue[0], $revenue[1]),
            'cash_in' => $this->metric($cash[0], $cash[1]),
            'expenses' => $this->metric($expenses[0], $expenses[1]),
            'net_cash_flow' => $this->metric($cash[0] - $expenses[0], $cash[1] - $expenses[1]),
            'outstanding' => round($outstanding, 2),
            'overdue_amount' => round((float) Invoice::query()->overdue()->sum('amount_due'), 2),
            'overdue_count' => Invoice::query()->overdue()->count(),
            'collection_rate' => $invoiced > 0 ? round(($invoiced - $outstanding) / $invoiced * 100, 1) : null,
            'margin' => $cash[0] > 0 ? round(($cash[0] - $expenses[0]) / $cash[0] * 100, 1) : null,
        ];
    }

    /**
     * @return array{value: float, previous: float, change: float|null}
     */
    private function metric(float $value, float $previous): array
    {
        return [
            'value' => round($value, 2),
            'previous' => round($previous, 2),
            'change' => abs($previous) > 0.009 ? round(($value - $previous) / abs($previous) * 100, 1) : null,
        ];
    }

    /**
     * @return array{labels: array<int, string>, months: array<int, string>, revenue: array<int, float>, cash_in: array<int, float>, expenses: array<int, float>}
     */
    public function monthlySeries(Carbon $today, int $months): array
    {
        $series = ['labels' => [], 'months' => [], 'revenue' => [], 'cash_in' => [], 'expenses' => []];

        for ($offset = $months - 1; $offset >= 0; $offset--) {
            $month = $today->copy()->startOfMonth()->subMonthsNoOverflow($offset);
            $from = $month->toDateString();
            $to = $month->copy()->endOfMonth()->toDateString();

            $series['labels'][] = $month->translatedFormat('M');
            $series['months'][] = $month->format('Y-m');
            $series['revenue'][] = round($this->revenueBetween($from, $to), 2);
            $series['cash_in'][] = round($this->cashInBetween($from, $to), 2);
            $series['expenses'][] = round($this->expensesBetween($from, $to), 2);
        }

        return $series;
    }

    /**
     * @return array<int, array{key: string, amount: float, count: int}>
     */
    private function aging(Carbon $today): array
    {
        $buckets = collect(['current', '1_30', '31_60', '61_90', '90_plus'])
            ->mapWithKeys(fn ($k) => [$k => ['key' => $k, 'amount' => 0.0, 'count' => 0]])->all();

        Invoice::query()->open()->select(['id', 'due_date', 'amount_due'])->lazyById(500)->each(function (Invoice $invoice) use (&$buckets, $today) {
            $days = $invoice->due_date ? (int) $invoice->due_date->diffInDays($today, false) : 0;
            $key = match (true) {
                $days <= 0 => 'current',
                $days <= 30 => '1_30',
                $days <= 60 => '31_60',
                $days <= 90 => '61_90',
                default => '90_plus',
            };
            $buckets[$key]['amount'] = round($buckets[$key]['amount'] + (float) $invoice->amount_due, 2);
            $buckets[$key]['count']++;
        });

        return array_values($buckets);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topCustomers(): array
    {
        $total = (float) Invoice::query()->issued()->sum('total');

        return Invoice::query()->issued()
            ->selectRaw('customer_id, SUM(total) as revenue, SUM(amount_due) as due, COUNT(*) as invoice_count')
            ->groupBy('customer_id')->orderByDesc('revenue')->with('customer:id,name,company_name')->limit(5)->get()
            ->map(fn (Invoice $row) => [
                'id' => $row->customer_id,
                'name' => $row->customer?->name ?? '—',
                'revenue' => round((float) $row->revenue, 2),
                'due' => round((float) $row->due, 2),
                'invoice_count' => (int) $row->invoice_count,
                'share' => $total > 0 ? round((float) $row->revenue / $total * 100, 1) : 0,
            ])->all();
    }

    /**
     * Best products over the last 12 months across invoices and direct sales.
     *
     * @return array<int, array{name: string, revenue: float, quantity: float}>
     */
    private function topProducts(Carbon $today): array
    {
        $since = $today->copy()->startOfMonth()->subMonthsNoOverflow(11)->toDateString();

        $fromInvoices = InvoiceItem::query()
            ->whereNotNull('product_id')
            ->whereHas('invoice', fn ($q) => $q->issued()->whereDate('issue_date', '>=', $since))
            ->selectRaw('product_id, SUM(line_subtotal) as revenue, SUM(quantity) as quantity')
            ->groupBy('product_id')->get()->keyBy('product_id');

        $fromSales = Sale::query()->whereDate('sale_date', '>=', $since)
            ->selectRaw('product_id, SUM(total_revenue) as revenue, SUM(quantity) as quantity')
            ->groupBy('product_id')->get()->keyBy('product_id');

        $names = Product::query()->whereIn('id', $fromInvoices->keys()->merge($fromSales->keys())->unique())->pluck('name', 'id');

        return $names->map(fn ($name, $id) => [
            'name' => $name,
            'revenue' => round((float) ($fromInvoices[$id]->revenue ?? 0) + (float) ($fromSales[$id]->revenue ?? 0), 2),
            'quantity' => round((float) ($fromInvoices[$id]->quantity ?? 0) + (float) ($fromSales[$id]->quantity ?? 0), 2),
        ])->sortByDesc('revenue')->take(5)->values()->all();
    }

    /**
     * @return array<int, array{name: string, amount: float, share: float}>
     */
    private function expenseCategories(Carbon $today): array
    {
        $rows = Expense::query()
            ->whereDate('spent_on', '>=', $today->copy()->startOfMonth()->subMonthsNoOverflow(11)->toDateString())
            ->selectRaw('expense_category_id, SUM(amount) as total')->groupBy('expense_category_id')
            ->with('category:id,name')->orderByDesc('total')->get();

        $grand = (float) $rows->sum('total');

        return $rows->take(6)->map(fn (Expense $row) => [
            'name' => $row->category?->name ?? '—',
            'amount' => round((float) $row->total, 2),
            'share' => $grand > 0 ? round((float) $row->total / $grand * 100, 1) : 0,
        ])->values()->all();
    }

    /**
     * @return array{average_days_to_pay: int|null, on_time_rate: float|null, sample_size: int}
     */
    private function paymentBehaviour(): array
    {
        $rows = InvoicePayment::query()->with('invoice:id,issue_date,due_date')->latest('paid_on')->limit(300)->get()
            ->filter(fn (InvoicePayment $p) => $p->invoice !== null)
            ->map(fn (InvoicePayment $p) => [
                'days' => $p->invoice->issue_date->diffInDays($p->paid_on, false),
                'on_time' => $p->invoice->due_date === null || $p->paid_on->lte($p->invoice->due_date),
            ]);

        return [
            'average_days_to_pay' => $rows->isNotEmpty() ? (int) round(max(0, $rows->avg('days'))) : null,
            'on_time_rate' => $rows->isNotEmpty() ? round($rows->where('on_time', true)->count() / $rows->count() * 100, 1) : null,
            'sample_size' => $rows->count(),
        ];
    }

    /**
     * @return array{low_stock: int, out_of_stock: int}
     */
    private function inventorySignals(): array
    {
        $products = Product::query()->where('is_active', true)->withSum('stocks as total_stock', 'quantity')->get(['id', 'minimum_stock']);

        return [
            'low_stock' => $products->filter(fn ($p) => (int) ($p->total_stock ?? 0) > 0 && (int) $p->total_stock < (int) $p->minimum_stock)->count(),
            'out_of_stock' => $products->filter(fn ($p) => (int) ($p->total_stock ?? 0) <= 0)->count(),
        ];
    }

    private function revenueBetween(string $from, string $to): float
    {
        return (float) Invoice::query()->issued()->whereDate('issue_date', '>=', $from)->whereDate('issue_date', '<=', $to)->sum('subtotal')
            - (float) Invoice::query()->issued()->whereDate('issue_date', '>=', $from)->whereDate('issue_date', '<=', $to)->sum('discount_total')
            + (float) Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('total_revenue');
    }

    private function cashInBetween(string $from, string $to): float
    {
        return (float) InvoicePayment::query()->whereDate('paid_on', '>=', $from)->whereDate('paid_on', '<=', $to)->sum('amount')
            + (float) Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('total_revenue');
    }

    private function expensesBetween(string $from, string $to): float
    {
        return (float) Expense::query()->whereDate('spent_on', '>=', $from)->whereDate('spent_on', '<=', $to)->sum('amount');
    }
}
