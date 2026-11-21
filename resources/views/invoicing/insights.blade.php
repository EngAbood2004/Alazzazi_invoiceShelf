@extends('layouts.app')
@section('title', __('Smart insights'))
@php
    $k = $summary['kpis'];
    $m = fn ($v) => \App\Support\Money::format($v, $currency);
    $toneClass = ['positive' => 'green', 'negative' => 'red', 'warning' => 'orange', 'info' => 'blue'];
    $toneIcon = ['positive' => 'bi-graph-up-arrow', 'negative' => 'bi-exclamation-octagon', 'warning' => 'bi-exclamation-triangle', 'info' => 'bi-lightbulb'];
    $agingLabels = ['current' => __('Not yet due'), '1_30' => __('1–30 days'), '31_60' => __('31–60 days'), '61_90' => __('61–90 days'), '90_plus' => __('90+ days')];
    $agingMax = max(1, collect($summary['aging'])->max('amount'));
    $f = $summary['forecast'];
@endphp
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Analytical insights') }}</div><h1 class="page-title">{{ __('Smart insights') }}</h1><p class="text-muted mb-0">{{ __('As of :date', ['date' => $summary['as_of']]) }}</p></div>
    <a href="{{ route('insights', ['refresh' => 1]) }}" class="btn btn-light border"><i class="bi bi-arrow-clockwise me-1"></i>{{ __('Refresh') }}</a>
</div>

<div class="row g-3 mb-4">
    @foreach([
        [__('Revenue this month'), $k['revenue'], 'blue', 'bi-receipt', false],
        [__('Cash received this month'), $k['cash_in'], 'green', 'bi-cash-coin', false],
        [__('Expenses this month'), $k['expenses'], 'red', 'bi-credit-card', true],
        [__('Net cash flow'), $k['net_cash_flow'], 'violet', 'bi-activity', false],
    ] as [$label, $metric, $color, $icon, $invert])
        <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body">
            <div class="d-flex justify-content-between"><div class="small text-muted">{{ $label }}</div><div class="icon-box {{ $color }}"><i class="bi {{ $icon }}"></i></div></div>
            <div class="fs-4 fw-bold mt-1">{{ $m($metric['value']) }}</div>
            @if($metric['change'] !== null)
                @php($good = $invert ? $metric['change'] <= 0 : $metric['change'] >= 0)
                <span class="badge rounded-pill {{ $good ? 'text-bg-success' : 'text-bg-danger' }} bg-opacity-75"><i class="bi {{ $metric['change'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i> {{ abs($metric['change']) }}%</span> <span class="small text-muted">{{ __('vs. last month') }}</span>
            @else <span class="small text-muted">{{ __('No comparison yet') }}</span> @endif
        </div></div></div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-8"><div class="card h-100"><div class="card-header d-flex justify-content-between flex-wrap gap-2">
        <div><div class="fw-bold">{{ __('Revenue, cash and expenses') }}</div><div class="small text-muted">{{ __('Last 12 months and a 3-month cash forecast') }}</div></div>
        <span class="badge text-bg-light border align-self-center">{{ $f['method'] === 'holt_winters' ? __('Seasonal model (Holt-Winters)') : __('Linear trend') }} · {{ __('Confidence') }}: {{ ['high' => __('High'), 'medium' => __('Medium'), 'low' => __('Low')][$f['confidence']] }}@if($f['mape'] !== null) · {{ __('error') }} {{ $f['mape'] }}%@endif</span>
    </div><div class="card-body"><div style="height:320px"><canvas id="trendChart"></canvas></div>
        <div class="row text-center small mt-3">@foreach($f['labels'] as $i => $label)<div class="col"><div class="text-muted">{{ $label }}</div><div class="fw-bold">{{ $m($f['values'][$i]) }}</div></div>@endforeach</div>
        @if($f['method'] !== 'holt_winters')<p class="small text-muted mb-0 mt-2"><i class="bi bi-info-circle me-1"></i>{{ __('The seasonal model starts automatically after 24 months of history (now: :n).', ['n' => $f['history_months']]) }}</p>@endif
    </div></div></div>

    <div class="col-xl-4"><div class="card h-100"><div class="card-header d-flex justify-content-between align-items-center">
        <div class="fw-bold"><i class="bi bi-stars text-primary me-1"></i>{{ __('AI insights') }}</div>
        <span class="badge {{ $ai['source'] === 'openai' ? 'text-bg-primary' : 'text-bg-light border' }}">{{ $ai['source'] === 'openai' ? 'OpenAI · '.$ai['model'] : __('Rule-based') }}</span>
    </div><div class="card-body d-flex flex-column gap-2">
        @foreach($ai['insights'] as $insight)
            <div class="d-flex gap-2 p-2 rounded-3" style="background:#f8fafc;border:1px solid #eef2f7">
                <div class="icon-box {{ $toneClass[$insight['tone']] ?? 'blue' }} flex-shrink-0" style="width:34px;height:34px;font-size:.95rem"><i class="bi {{ $toneIcon[$insight['tone']] ?? 'bi-lightbulb' }}"></i></div>
                <div class="small"><div class="fw-bold">{{ $insight['title'] }}</div><div class="text-muted">{{ $insight['explanation'] }}</div>@if($insight['action'])<div class="mt-1"><i class="bi bi-arrow-return-left me-1 text-primary"></i>{{ $insight['action'] }}</div>@endif</div>
            </div>
        @endforeach
        @if($ai['source'] !== 'openai')<div class="small text-muted mt-auto"><i class="bi bi-key me-1"></i>{{ __('Add OPENAI_API_KEY to .env for AI explanations of the causes.') }}</div>@endif
    </div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4"><div class="card h-100"><div class="card-header fw-bold">{{ __('Receivables') }}</div><div class="card-body">
        <div class="metric-row"><span class="text-muted">{{ __('Outstanding') }}</span><span class="fw-bold">{{ $m($k['outstanding']) }}</span></div>
        <div class="metric-row"><span class="text-muted">{{ __('Overdue') }}</span><span class="fw-bold text-danger">{{ $m($k['overdue_amount']) }} ({{ $k['overdue_count'] }})</span></div>
        <div class="metric-row"><span class="text-muted">{{ __('Collection rate') }}</span><span class="fw-bold">{{ $k['collection_rate'] !== null ? $k['collection_rate'].'%' : '—' }}</span></div>
        <div class="metric-row"><span class="text-muted">{{ __('Average days to pay') }}</span><span class="fw-bold">{{ $summary['payment_behaviour']['average_days_to_pay'] ?? '—' }}</span></div>
        <div class="metric-row"><span class="text-muted">{{ __('Paid on time') }}</span><span class="fw-bold">{{ $summary['payment_behaviour']['on_time_rate'] !== null ? $summary['payment_behaviour']['on_time_rate'].'%' : '—' }}</span></div>
        <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="btn btn-sm btn-outline-danger w-100 mt-3">{{ __('Review overdue invoices') }}</a>
    </div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header fw-bold">{{ __('Receivables aging') }}</div><div class="card-body">
        @foreach($summary['aging'] as $bucket)
            <div class="mb-3"><div class="d-flex justify-content-between small"><span>{{ $agingLabels[$bucket['key']] }}</span><span><strong>{{ $m($bucket['amount']) }}</strong> · {{ $bucket['count'] }}</span></div>
            <div class="progress" style="height:8px"><div class="progress-bar {{ ['current' => 'bg-success', '1_30' => 'bg-warning', '31_60' => 'bg-orange', '61_90' => 'bg-danger', '90_plus' => 'bg-dark'][$bucket['key']] }}" style="width: {{ $bucket['amount'] > 0 ? max(3, $bucket['amount'] / $agingMax * 100) : 0 }}%; {{ $bucket['key'] === '31_60' ? 'background:#ea580c' : '' }}"></div></div></div>
        @endforeach
    </div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header fw-bold">{{ __('Top customers') }}</div><ul class="list-group list-group-flush">
        @forelse($summary['top_customers'] as $c)
            <li class="list-group-item"><div class="d-flex justify-content-between"><a href="{{ route('customers.show', $c['id']) }}" class="fw-semibold text-decoration-none">{{ $c['name'] }}</a><span class="fw-semibold">{{ $m($c['revenue']) }}</span></div><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:5px"><div class="progress-bar" style="width: {{ $c['share'] }}%"></div></div><span class="small text-muted">{{ $c['share'] }}%</span></div></li>
        @empty <li class="list-group-item text-muted small">{{ __('No data yet') }}</li> @endforelse
    </ul></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header fw-bold">{{ __('Best-selling products (12 months)') }}</div><ul class="list-group list-group-flush">
        @forelse($summary['top_products'] as $p)<li class="list-group-item d-flex justify-content-between"><span>{{ $p['name'] }} <span class="small text-muted">× {{ rtrim(rtrim((string) $p['quantity'], '0'), '.') }}</span></span><span class="fw-semibold">{{ $m($p['revenue']) }}</span></li>@empty<li class="list-group-item text-muted small">{{ __('No data yet') }}</li>@endforelse
    </ul></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header fw-bold">{{ __('Where the money goes (12 months)') }}</div><div class="card-body">
        @forelse($summary['expense_categories'] as $c)<div class="mb-3"><div class="d-flex justify-content-between small"><span>{{ $c['name'] }}</span><span class="text-muted">{{ $c['share'] }}% · {{ $m($c['amount']) }}</span></div><div class="progress" style="height:6px"><div class="progress-bar bg-danger" style="width: {{ $c['share'] }}%"></div></div></div>@empty<p class="text-muted small mb-0">{{ __('No data yet') }}</p>@endforelse
    </div></div></div>
</div>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
(() => {
    const t = @json($summary['trend']);
    const f = @json($summary['forecast']);
    const labels = [...t.labels, ...f.labels];
    const pad = (a) => [...a, ...f.labels.map(() => null)];
    const forecast = [...t.cash_in.slice(0, -1).map(() => null), t.cash_in.at(-1), ...f.values];
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: { labels, datasets: [
            { label: @json(__('Revenue')), data: pad(t.revenue), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .35, pointRadius: 0 },
            { label: @json(__('Cash received')), data: pad(t.cash_in), borderColor: '#16a34a', tension: .35, pointRadius: 0 },
            { label: @json(__('Expenses')), data: pad(t.expenses), borderColor: '#dc2626', tension: .35, pointRadius: 0 },
            { label: @json(__('Forecast')), data: forecast, borderColor: '#16a34a', borderDash: [6, 6], tension: .35, pointRadius: (c) => c.dataIndex >= t.labels.length ? 4 : 0 },
        ]},
        options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
    });
})();
</script>
@endsection
