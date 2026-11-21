@extends('layouts.app')

@section('title', __('Sales'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="eyebrow">{{ __('Analytics') }}</div>
        <h1 class="page-title">{{ __('Sales analytics') }}</h1>
        <p class="text-muted mb-0">{{ __('Record sales, track remaining stock, and review profit and loss over time.') }}</p>
    </div>
</div>

<p class="small text-muted mb-4">{{ __('The rate is calculated as profit or loss divided by the cost of goods sold.') }}</p>

<div class="card p-3 mb-4">
    <form method="GET" action="{{ route('sales.index') }}" class="row g-3 align-items-end">
        <div class="col-sm-5 col-lg-4">
            <label for="period" class="form-label">{{ __('Analysis period') }}</label>
            <select id="period" name="period" class="form-select">
                <option value="daily" @selected($period === 'daily')>{{ __('Daily') }}</option>
                <option value="monthly" @selected($period === 'monthly')>{{ __('Monthly') }}</option>
                <option value="yearly" @selected($period === 'yearly')>{{ __('Yearly') }}</option>
            </select>
        </div>
        <div class="col-sm-4 col-lg-3">
            <label for="year" class="form-label">{{ __('Year') }}</label>
            <select id="year" name="year" class="form-select">
                @foreach($years as $availableYear)
                    <option value="{{ $availableYear }}" @selected($year === $availableYear)>{{ $availableYear }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-3 col-lg-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>{{ __('Apply filters') }}</button>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    @foreach([
        [__('Sales revenue'), number_format($summary['revenue'], 2), 'bi-cash-stack', 'green'],
        [__('Cost of goods sold'), number_format($summary['cost'], 2), 'bi-box-seam', 'blue'],
        [__('Net profit or loss'), number_format($summary['profit'], 2), 'bi-graph-up-arrow', $summary['profit'] >= 0 ? 'teal' : 'red'],
        [__('Profit or loss rate'), $summary['profit_percentage'] === null ? '—' : number_format($summary['profit_percentage'], 2).'%', 'bi-percent', $summary['profit'] >= 0 ? 'violet' : 'red'],
        [__('Items sold'), number_format($summary['quantity']), 'bi-cart-check', 'orange'],
    ] as [$label, $value, $icon, $color])
        <div class="col-6 col-xl">
            <div class="card p-3 h-100">
                <div class="icon-box {{ $color }} mb-3"><i class="bi {{ $icon }}"></i></div>
                <div class="text-muted small">{{ $label }}</div>
                <div class="fs-4 fw-bold">{{ $value }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card p-3 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 fw-bold mb-1">{{ __('Revenue and profit trend') }}</h2>
            <small class="text-muted">{{ $start->toDateString() }} — {{ $end->toDateString() }}</small>
        </div>
        <span class="badge {{ $summary['profit'] >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
            {{ __('Sales records') }}: {{ number_format($summary['sales_count']) }}
        </span>
    </div>
    <canvas id="salesChart" height="100"></canvas>
</div>

<div class="card mb-4">
    <div class="card-header fw-bold">{{ __('Record a sale') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ route('sales.store') }}" id="saleForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label for="product_id" class="form-label">{{ __('Product') }}</label>
                    <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                        <option value="">{{ __('Choose a product') }}</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                data-price="{{ $product->selling_price }}"
                                data-stocks='@json($product->stocks->pluck("quantity", "warehouse_id"))'
                                @selected(old('product_id') == $product->id)>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                    <select id="warehouse_id" name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
                        <option value="">{{ __('Choose a warehouse') }}</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 col-xl-2">
                    <label for="quantity" class="form-label">{{ __('Quantity sold') }}</label>
                    <input id="quantity" name="quantity" type="number" min="1" step="1" value="{{ old('quantity', 1) }}" class="form-control @error('quantity') is-invalid @enderror" required>
                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 col-xl-2">
                    <label for="unit_price" class="form-label">{{ __('Sale price per item') }}</label>
                    <input id="unit_price" name="unit_price" type="number" min="0" step="0.01" value="{{ old('unit_price') }}" class="form-control @error('unit_price') is-invalid @enderror" required>
                    @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 col-xl-2">
                    <label for="sale_date" class="form-label">{{ __('Sale date') }}</label>
                    <input id="sale_date" name="sale_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('sale_date', now()->toDateString()) }}" class="form-control @error('sale_date') is-invalid @enderror" required>
                    @error('sale_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                <div id="stockSummary" class="small text-muted" aria-live="polite">{{ __('Select a product and warehouse to see available stock.') }}</div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>{{ __('Save sale') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header fw-bold">{{ __('Sales records') }} · {{ $start->toDateString() }} — {{ $end->toDateString() }}</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Product') }}</th>
                    <th>{{ __('Warehouse') }}</th>
                    <th>{{ __('Quantity sold') }}</th>
                    <th>{{ __('Remaining stock in this warehouse') }}</th>
                    <th>{{ __('Sale price per item') }}</th>
                    <th>{{ __('Sales revenue') }}</th>
                    <th>{{ __('Cost of goods sold') }}</th>
                    <th>{{ __('Profit or loss') }}</th>
                    <th>{{ __('Profit or loss rate') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td>{{ $sale->sale_date->format('Y-m-d') }}</td>
                        <td><strong>{{ $sale->product_name }}</strong><small class="d-block text-muted">{{ $sale->sku }}</small></td>
                        <td>{{ $sale->warehouse?->name ?? '—' }}</td>
                        <td>{{ number_format($sale->quantity) }}</td>
                        <td>{{ $sale->stock_after === null ? '—' : number_format($sale->stock_after).' '.($sale->product?->unit ?? '') }}</td>
                        <td>{{ number_format((float) $sale->unit_price, 2) }}</td>
                        <td>{{ number_format((float) $sale->total_revenue, 2) }}</td>
                        <td>{{ number_format((float) $sale->total_cost, 2) }}</td>
                        <td class="{{ $sale->profit >= 0 ? 'text-success' : 'text-danger' }} fw-bold">{{ number_format((float) $sale->profit, 2) }}</td>
                        <td class="{{ $sale->profit >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $sale->profitPercentage() === null ? '—' : number_format($sale->profitPercentage(), 2).'%' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">{{ __('No sales found for this period.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $sales->links() }}</div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('salesChart'), {
        type: 'bar',
        data: {
            labels: @json($chart->pluck('label')),
            datasets: [
                { label: @json(__('Sales revenue')), data: @json($chart->pluck('revenue')), backgroundColor: 'rgba(37, 99, 235, .65)', borderRadius: 5 },
                {
                    label: @json(__('Profit or loss')),
                    data: @json($chart->pluck('profit')),
                    backgroundColor: (context) => context.raw < 0 ? 'rgba(220, 38, 38, .65)' : 'rgba(22, 163, 74, .65)',
                    borderRadius: 5
                }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
    });

    const productSelect = document.getElementById('product_id');
    const warehouseSelect = document.getElementById('warehouse_id');
    const quantityInput = document.getElementById('quantity');
    const priceInput = document.getElementById('unit_price');
    const stockSummary = document.getElementById('stockSummary');
    const labels = {
        available: @json(__('Available stock')),
        remaining: @json(__('Remaining after this sale')),
        notEnough: @json(__('The requested quantity exceeds available stock.')),
        choose: @json(__('Select a product and warehouse to see available stock.'))
    };

    function updateStockSummary() {
        const product = productSelect.selectedOptions[0];
        const warehouseId = warehouseSelect.value;
        if (!product || !product.value || !warehouseId) {
            stockSummary.textContent = labels.choose;
            return;
        }

        const stocks = JSON.parse(product.dataset.stocks || '{}');
        const available = Number(stocks[warehouseId] || 0);
        const requested = Number(quantityInput.value || 0);
        const remaining = available - requested;
        stockSummary.textContent = `${labels.available}: ${available} · ${labels.remaining}: ${Math.max(remaining, 0)}`;
        stockSummary.classList.toggle('text-danger', requested > available);
        stockSummary.classList.toggle('text-success', requested <= available);
        if (requested > available) stockSummary.textContent += ` · ${labels.notEnough}`;
    }

    productSelect.addEventListener('change', () => {
        const product = productSelect.selectedOptions[0];
        if (product && product.value) priceInput.value = product.dataset.price;
        updateStockSummary();
    });
    warehouseSelect.addEventListener('change', updateStockSummary);
    quantityInput.addEventListener('input', updateStockSummary);
    updateStockSummary();
</script>
@endsection
