@extends('layouts.app')
@section('title', __('Recurring invoices'))
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Invoicing') }}</div><h1 class="page-title">{{ __('Recurring invoices') }}</h1><p class="text-muted mb-0">{{ __('Invoices are created automatically every day at 06:00 for the schedules that are due.') }}</p></div>
    <a href="{{ route('recurring-invoices.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('New schedule') }}</a>
</div>
@php($freq = ['weekly' => __('Weekly'), 'monthly' => __('Monthly'), 'quarterly' => __('Quarterly'), 'yearly' => __('Yearly')])
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>{{ __('Customer') }}</th><th>{{ __('Frequency') }}</th><th>{{ __('Next invoice') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Created') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
    <tbody>
    @forelse($schedules as $schedule)
        @php($amount = collect($schedule->items)->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_price']))
        <tr><td class="fw-semibold">{{ $schedule->customer->name }}</td><td>{{ $freq[$schedule->frequency] ?? $schedule->frequency }}</td><td>{{ $schedule->next_run_date->format('Y-m-d') }}</td><td>{{ \App\Support\Money::format($amount, $schedule->currency) }}</td><td>{{ $schedule->invoices_count }}</td>
            <td>@if($schedule->is_active)<span class="badge text-bg-success">{{ __('Active') }}</span>@else<span class="badge text-bg-secondary">{{ __('Stopped') }}</span>@endif @if($schedule->auto_send)<span class="badge text-bg-light border">{{ __('Auto send') }}</span>@endif</td>
            <td class="text-end text-nowrap"><form method="POST" action="{{ route('recurring-invoices.run', $schedule) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary" title="{{ __('Create the next invoice now') }}"><i class="bi bi-lightning-charge"></i></button></form> <a href="{{ route('recurring-invoices.edit', $schedule) }}" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a></td></tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-arrow-repeat fs-2 d-block mb-2"></i>{{ __('No recurring schedules yet.') }}</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
