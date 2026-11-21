@extends('layouts.app')
@section('title', __('Estimates'))
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Invoicing') }}</div><h1 class="page-title">{{ __('Estimates') }}</h1></div>
    <a href="{{ route('estimates.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('New estimate') }}</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Date') }}</th><th>{{ __('Valid until') }}</th><th>{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr></thead>
    <tbody>
    @forelse($estimates as $estimate)
        <tr onclick="location.href='{{ route('estimates.show', $estimate) }}'" style="cursor:pointer"><td class="fw-semibold">{{ $estimate->number }}</td><td>{{ $estimate->customer->name }}</td><td>{{ $estimate->issue_date->format('Y-m-d') }}</td><td>{{ $estimate->expiry_date?->format('Y-m-d') }}</td><td>{{ \App\Support\Money::format($estimate->total, $estimate->currency) }}</td><td>@include('invoicing.partials.status', ['status' => $estimate->isExpired() ? 'expired' : $estimate->status])</td></tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-file-earmark-text fs-2 d-block mb-2"></i>{{ __('No estimates yet.') }}</td></tr>
    @endforelse
    </tbody>
</table></div>@if($estimates->hasPages())<div class="card-footer bg-white">{{ $estimates->links() }}</div>@endif</div>
@endsection
