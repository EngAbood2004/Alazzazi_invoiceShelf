@extends('layouts.app')
@section('title', $customer->name)
@section('content')
@php($m = fn ($v) => \App\Support\Money::format($v, $customer->currency))
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Customer') }}</div><h1 class="page-title">{{ $customer->name }}</h1>@if($customer->company_name)<p class="text-muted mb-0">{{ $customer->company_name }}</p>@endif</div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary"><i class="bi bi-receipt me-1"></i>{{ __('New invoice') }}</a>
        <a href="{{ route('estimates.create', ['customer_id' => $customer->id]) }}" class="btn btn-light border"><i class="bi bi-file-earmark-text me-1"></i>{{ __('New estimate') }}</a>
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-light border"><i class="bi bi-pencil"></i></a>
    </div>
</div>
<div class="row g-3 mb-4">
    @foreach([[__('Invoiced'), $stats['invoiced'], 'blue', 'bi-receipt'], [__('Paid'), $stats['paid'], 'green', 'bi-cash-coin'], [__('Balance due'), $stats['due'], 'orange', 'bi-hourglass-split'], [__('Overdue'), $stats['overdue'], 'red', 'bi-exclamation-triangle']] as [$label, $value, $color, $icon])
        <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><div class="icon-box {{ $color }}"><i class="bi {{ $icon }}"></i></div><div><div class="small text-muted">{{ $label }}</div><div class="fs-5 fw-bold">{{ $m($value) }}</div></div></div></div></div>
    @endforeach
</div>
<div class="row g-3">
    <div class="col-lg-4"><div class="card h-100"><div class="card-header fw-bold">{{ __('Contact') }}</div><div class="card-body">
        @foreach([[__('Email'), $customer->email], [__('Phone'), $customer->phone], [__('WhatsApp'), $customer->whatsapp], [__('Tax number'), $customer->tax_number], [__('Address'), trim($customer->address.' '.$customer->city.' '.$customer->country)], [__('Currency'), $customer->currency]] as [$label, $value])
            @if($value)<div class="metric-row"><span class="text-muted">{{ $label }}</span><span class="fw-semibold">{{ $value }}</span></div>@endif
        @endforeach
        @if($customer->notes)<p class="small text-muted mt-3 mb-0">{{ $customer->notes }}</p>@endif
    </div></div></div>
    <div class="col-lg-8"><div class="card"><div class="card-header fw-bold">{{ __('Invoices') }}</div><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Date') }}</th><th>{{ __('Due date') }}</th><th>{{ __('Total') }}</th><th>{{ __('Balance due') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($customer->invoices as $invoice)<tr><td><a href="{{ route('invoices.show', $invoice) }}" class="fw-semibold text-decoration-none">{{ $invoice->number }}</a></td><td>{{ $invoice->issue_date->format('Y-m-d') }}</td><td>{{ $invoice->due_date?->format('Y-m-d') }}</td><td>{{ $m($invoice->total) }}</td><td>{{ $m($invoice->amount_due) }}</td><td>@include('invoicing.partials.status', ['status' => $invoice->displayStatus()])</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">{{ __('No invoices yet.') }}</td></tr>@endforelse</tbody>
    </table></div></div>
    @if($customer->estimates->isNotEmpty())<div class="card mt-3"><div class="card-header fw-bold">{{ __('Estimates') }}</div><div class="table-responsive"><table class="table mb-0"><tbody>@foreach($customer->estimates as $estimate)<tr><td><a href="{{ route('estimates.show', $estimate) }}" class="fw-semibold text-decoration-none">{{ $estimate->number }}</a></td><td>{{ $estimate->issue_date->format('Y-m-d') }}</td><td>{{ $m($estimate->total) }}</td><td>@include('invoicing.partials.status', ['status' => $estimate->isExpired() ? 'expired' : $estimate->status])</td></tr>@endforeach</tbody></table></div></div>@endif
    </div>
</div>
@endsection
