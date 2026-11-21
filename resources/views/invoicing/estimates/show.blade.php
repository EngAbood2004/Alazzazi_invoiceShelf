@extends('layouts.app')
@section('title', $estimate->number)
@php($m = fn ($v) => \App\Support\Money::format($v, $estimate->currency))
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Estimate') }}</div><h1 class="page-title d-flex align-items-center gap-2">{{ $estimate->number }} @include('invoicing.partials.status', ['status' => $estimate->isExpired() ? 'expired' : $estimate->status])</h1><p class="text-muted mb-0">{{ $estimate->customer->displayName() }}</p></div>
    <div class="d-flex flex-wrap gap-2">
        @if($estimate->status !== 'converted')<a href="{{ route('estimates.edit', $estimate) }}" class="btn btn-light border"><i class="bi bi-pencil me-1"></i>{{ __('Edit') }}</a>@endif
        <a href="{{ route('estimates.pdf', $estimate) }}" target="_blank" class="btn btn-light border"><i class="bi bi-filetype-pdf me-1"></i>{{ $pdfEnabled ? __('PDF') : __('Print') }}</a>
        @if($estimate->status === 'draft')<form method="POST" action="{{ route('estimates.status', $estimate) }}">@csrf<input type="hidden" name="status" value="sent"><button class="btn btn-outline-primary"><i class="bi bi-send me-1"></i>{{ __('Mark as sent') }}</button></form>@endif
        @if(in_array($estimate->status, ['draft', 'sent']))
            <form method="POST" action="{{ route('estimates.status', $estimate) }}">@csrf<input type="hidden" name="status" value="accepted"><button class="btn btn-outline-success">{{ __('Accepted') }}</button></form>
            <form method="POST" action="{{ route('estimates.status', $estimate) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-outline-danger">{{ __('Rejected') }}</button></form>
        @endif
        @if($estimate->status !== 'converted' && $estimate->status !== 'rejected')<form method="POST" action="{{ route('estimates.convert', $estimate) }}">@csrf<button class="btn btn-primary"><i class="bi bi-arrow-repeat me-1"></i>{{ __('Convert to invoice') }}</button></form>@endif
        @if($estimate->invoice)<a href="{{ route('invoices.show', $estimate->invoice) }}" class="btn btn-primary"><i class="bi bi-receipt me-1"></i>{{ $estimate->invoice->number }}</a>@endif
    </div>
</div>
@if($estimate->status !== 'draft')<div class="alert alert-light border small"><i class="bi bi-link-45deg me-1"></i>{{ __('Customer link:') }} <code dir="ltr">{{ route('public.estimates.pdf', $estimate->public_token) }}</code></div>@endif
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>{{ __('Description') }}</th><th class="text-end">{{ __('Qty') }}</th><th class="text-end">{{ __('Unit price') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
        <tbody>@foreach($estimate->items as $item)<tr><td>{{ $item->description }}</td><td class="text-end">{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}</td><td class="text-end">{{ $m($item->unit_price) }}</td><td class="text-end fw-semibold">{{ $m($item->line_total) }}</td></tr>@endforeach</tbody>
    </table></div>
    <div class="row justify-content-end mt-3"><div class="col-md-5">
        <div class="metric-row"><span class="text-muted">{{ __('Subtotal') }}</span><span>{{ $m($estimate->subtotal) }}</span></div>
        @if((float) $estimate->discount_total > 0)<div class="metric-row"><span class="text-muted">{{ __('Discount') }}</span><span class="text-danger">- {{ $m($estimate->discount_total) }}</span></div>@endif
        <div class="metric-row"><span class="text-muted">{{ __('Tax') }}</span><span>{{ $m($estimate->tax_total) }}</span></div>
        <div class="metric-row fs-5"><span class="fw-bold">{{ __('Total') }}</span><span class="fw-bold text-primary">{{ $m($estimate->total) }}</span></div>
    </div></div>
</div></div>
@endsection
