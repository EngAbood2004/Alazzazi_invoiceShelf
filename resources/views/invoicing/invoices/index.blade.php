@extends('layouts.app')
@section('title', __('Invoices'))
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Invoicing') }}</div><h1 class="page-title">{{ __('Invoices') }}</h1></div>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('New invoice') }}</a>
</div>
<div class="row g-3 mb-4">
    @foreach([[__('Outstanding'), \App\Support\Money::format($summary['outstanding']), 'blue', 'bi-hourglass-split', null], [__('Overdue'), \App\Support\Money::format($summary['overdue']), 'red', 'bi-exclamation-triangle', __(':count invoices', ['count' => $summary['overdue_count']])], [__('Collected this month'), \App\Support\Money::format($summary['paid_this_month']), 'green', 'bi-cash-coin', null]] as [$label, $value, $color, $icon, $note])
        <div class="col-md-4"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><div class="icon-box {{ $color }}"><i class="bi {{ $icon }}"></i></div><div><div class="small text-muted">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div>@if($note)<div class="small text-muted">{{ $note }}</div>@endif</div></div></div></div>
    @endforeach
</div>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <ul class="nav nav-pills gap-1 small">
            @foreach(['' => __('All'), 'draft' => __('Draft'), 'unpaid' => __('Unpaid'), 'overdue' => __('Overdue'), 'paid' => __('Paid'), 'cancelled' => __('Cancelled')] as $key => $label)
                <li class="nav-item"><a class="nav-link py-1 px-3 {{ $status === $key ? 'active' : '' }}" href="{{ route('invoices.index', array_filter(['status' => $key, 'search' => request('search')])) }}">{{ $label }}</a></li>
            @endforeach
        </ul>
        <form method="GET" class="d-flex gap-2"><input type="hidden" name="status" value="{{ $status }}"><input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="{{ __('Number or customer') }}"><button class="btn btn-sm btn-light border"><i class="bi bi-search"></i></button></form>
    </div>
    <div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Date') }}</th><th>{{ __('Due date') }}</th><th>{{ __('Total') }}</th><th>{{ __('Balance due') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
        @forelse($invoices as $invoice)
            <tr onclick="location.href='{{ route('invoices.show', $invoice) }}'" style="cursor:pointer">
                <td class="fw-semibold">{{ $invoice->number }}</td>
                <td>{{ $invoice->customer->name }}</td>
                <td>{{ $invoice->issue_date->format('Y-m-d') }}</td>
                <td class="{{ $invoice->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ $invoice->due_date?->format('Y-m-d') }}@if($invoice->isOverdue())<div class="small">{{ __(':days days late', ['days' => $invoice->daysOverdue()]) }}</div>@endif</td>
                <td>{{ \App\Support\Money::format($invoice->total, $invoice->currency) }}</td>
                <td class="fw-semibold">{{ \App\Support\Money::format($invoice->amount_due, $invoice->currency) }}</td>
                <td>@include('invoicing.partials.status', ['status' => $invoice->displayStatus()])</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-receipt fs-2 d-block mb-2"></i>{{ __('No invoices found.') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($invoices->hasPages())<div class="card-footer bg-white">{{ $invoices->links() }}</div>@endif
</div>
@endsection
