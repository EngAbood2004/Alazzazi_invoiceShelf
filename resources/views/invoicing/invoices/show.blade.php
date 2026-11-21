@extends('layouts.app')
@section('title', $invoice->number)
@php($m = fn ($v) => \App\Support\Money::format($v, $invoice->currency))
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div>
        <div class="eyebrow">{{ __('Invoice') }}</div>
        <h1 class="page-title d-flex align-items-center gap-2">{{ $invoice->number }} @include('invoicing.partials.status', ['status' => $invoice->displayStatus()])</h1>
        <p class="text-muted mb-0"><a href="{{ route('customers.show', $invoice->customer) }}" class="text-decoration-none">{{ $invoice->customer->displayName() }}</a></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($invoice->isEditable())<a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-light border"><i class="bi bi-pencil me-1"></i>{{ __('Edit') }}</a>@endif
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn btn-light border"><i class="bi bi-filetype-pdf me-1"></i>{{ $pdfEnabled ? __('PDF') : __('Print') }}</a>
        @if($invoice->status === 'draft')
            <form method="POST" action="{{ route('invoices.send', $invoice) }}">@csrf<button class="btn btn-primary"><i class="bi bi-send me-1"></i>{{ __('Mark as sent') }}</button></form>
        @endif
        @if(! in_array($invoice->status, ['draft', 'cancelled', 'paid']))
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="bi bi-cash-coin me-1"></i>{{ __('Record payment') }}</button>
            <form method="POST" action="{{ route('invoices.remind', $invoice) }}">@csrf<button class="btn btn-outline-warning"><i class="bi bi-bell me-1"></i>{{ __('Send reminder') }}</button></form>
        @endif
        @if(! in_array($invoice->status, ['cancelled', 'paid']) && (float) $invoice->amount_paid === 0.0)
            <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" onsubmit="return confirm(@json(__('Cancel this invoice? Stock taken for it will be returned.')))">@csrf<button class="btn btn-outline-danger"><i class="bi bi-x-circle"></i></button></form>
        @endif
    </div>
</div>

@if($invoice->status !== 'draft')
<div class="alert alert-light border d-flex flex-wrap align-items-center gap-2 justify-content-between">
    <span><i class="bi bi-link-45deg me-1"></i>{{ __('Customer link (view, download and pay online):') }} <code dir="ltr" id="publicLink">{{ $invoice->publicUrl() }}</code></span>
    <span class="d-flex gap-2">
        <button class="btn btn-sm btn-light border" onclick="navigator.clipboard.writeText(document.getElementById('publicLink').textContent)"><i class="bi bi-clipboard"></i> {{ __('Copy') }}</button>
        @if($invoice->customer->whatsappNumber())<a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ ltrim($invoice->customer->whatsappNumber(), '+') }}?text={{ rawurlencode(__('Invoice :number: :url', ['number' => $invoice->number, 'url' => $invoice->publicUrl()])) }}"><i class="bi bi-whatsapp"></i> WhatsApp</a>@endif
    </span>
</div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3"><div class="card-body">
            <div class="row small mb-3">
                <div class="col-sm-4"><div class="text-muted">{{ __('Date') }}</div><div class="fw-semibold">{{ $invoice->issue_date->format('Y-m-d') }}</div></div>
                <div class="col-sm-4"><div class="text-muted">{{ __('Due date') }}</div><div class="fw-semibold {{ $invoice->isOverdue() ? 'text-danger' : '' }}">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</div></div>
                <div class="col-sm-4"><div class="text-muted">{{ __('Stock') }}</div><div class="fw-semibold">{{ $invoice->warehouse ? ($invoice->stock_deducted ? __('Deducted from :name', ['name' => $invoice->warehouse->name]) : __('Will be deducted from :name when sent', ['name' => $invoice->warehouse->name])) : __('Not linked to stock') }}</div></div>
            </div>
            <div class="table-responsive"><table class="table mb-0">
                <thead><tr><th>{{ __('Description') }}</th><th class="text-end">{{ __('Qty') }}</th><th class="text-end">{{ __('Unit price') }}</th><th class="text-end">{{ __('Tax') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
                <tbody>@foreach($invoice->items as $item)<tr><td>{{ $item->description }}@if($item->product)<div class="small text-muted">{{ $item->product->sku }}</div>@endif</td><td class="text-end">{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}</td><td class="text-end">{{ $m($item->unit_price) }}</td><td class="text-end">{{ (float) $item->tax_rate > 0 ? rtrim(rtrim((string) $item->tax_rate, '0'), '.').'%' : '—' }}</td><td class="text-end fw-semibold">{{ $m($item->line_total) }}</td></tr>@endforeach</tbody>
            </table></div>
            <div class="row justify-content-end mt-3"><div class="col-md-6">
                <div class="metric-row"><span class="text-muted">{{ __('Subtotal') }}</span><span>{{ $m($invoice->subtotal) }}</span></div>
                @if((float) $invoice->discount_total > 0)<div class="metric-row"><span class="text-muted">{{ __('Discount') }}</span><span class="text-danger">- {{ $m($invoice->discount_total) }}</span></div>@endif
                <div class="metric-row"><span class="text-muted">{{ __('Tax') }}</span><span>{{ $m($invoice->tax_total) }}</span></div>
                <div class="metric-row fs-5"><span class="fw-bold">{{ __('Total') }}</span><span class="fw-bold">{{ $m($invoice->total) }}</span></div>
                <div class="metric-row"><span class="text-muted">{{ __('Paid') }}</span><span class="text-success">{{ $m($invoice->amount_paid) }}</span></div>
                <div class="metric-row fs-5"><span class="fw-bold">{{ __('Balance due') }}</span><span class="fw-bold text-primary">{{ $m($invoice->amount_due) }}</span></div>
            </div></div>
            @if($invoice->notes)<hr><p class="small mb-0"><strong>{{ __('Notes') }}:</strong> {{ $invoice->notes }}</p>@endif
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-header fw-bold">{{ __('Payments') }}</div><ul class="list-group list-group-flush">
            @forelse($invoice->payments as $payment)
                <li class="list-group-item d-flex justify-content-between"><span><span class="fw-semibold">{{ $m($payment->amount) }}</span><div class="small text-muted">{{ $payment->paid_on->format('Y-m-d') }} · {{ $payment->gateway === 'lahza' ? __('Online (Lahza)') : ($payment->paymentMethod?->name ?? __('Manual')) }}@if($payment->reference) · {{ $payment->reference }}@endif</div></span><i class="bi bi-check-circle-fill text-success"></i></li>
            @empty
                <li class="list-group-item text-muted small">{{ __('No payments yet.') }}</li>
            @endforelse
        </ul></div>
        <div class="card"><div class="card-header fw-bold">{{ __('Reminders') }}</div><ul class="list-group list-group-flush">
            @forelse($invoice->reminders as $reminder)
                <li class="list-group-item small d-flex justify-content-between"><span><i class="bi {{ $reminder->channel === 'whatsapp' ? 'bi-whatsapp text-success' : 'bi-envelope' }} me-1"></i>{{ $reminder->created_at->format('Y-m-d H:i') }} · {{ __(':days days late', ['days' => $reminder->days_overdue]) }}@if($reminder->error_message)<div class="text-danger">{{ \Illuminate\Support\Str::limit($reminder->error_message, 120) }}</div>@endif</span><span class="badge text-bg-{{ $reminder->status === 'sent' ? 'success' : 'danger' }} align-self-start">{{ $reminder->status === 'sent' ? __('Sent') : __('Failed') }}</span></li>
            @empty
                <li class="list-group-item text-muted small">{{ __('Automatic reminders are sent by email and WhatsApp after the due date.') }}</li>
            @endforelse
        </ul></div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('invoices.payments.store', $invoice) }}">@csrf
    <div class="modal-header"><h5 class="modal-title">{{ __('Record payment') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-6"><label class="form-label">{{ __('Amount') }}</label><input type="number" step="0.01" min="0.01" max="{{ $invoice->amount_due }}" name="amount" value="{{ $invoice->amount_due }}" class="form-control" required></div>
        <div class="col-6"><label class="form-label">{{ __('Date') }}</label><input type="date" name="paid_on" value="{{ today()->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" class="form-control" required></div>
        <div class="col-6"><label class="form-label">{{ __('Payment method') }}</label><select name="payment_method_id" class="form-select"><option value="">—</option>@foreach($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select></div>
        <div class="col-6"><label class="form-label">{{ __('Reference') }}</label><input name="reference" class="form-control"></div>
        <div class="col-12"><label class="form-label">{{ __('Notes') }}</label><textarea name="notes" rows="2" class="form-control"></textarea></div>
    </div></div>
    <div class="modal-footer"><button class="btn btn-success"><i class="bi bi-check2 me-1"></i>{{ __('Save payment') }}</button></div>
</form></div></div>
@if($errors->any())<script>document.addEventListener('DOMContentLoaded',()=>{ if (window.bootstrap) new bootstrap.Modal(document.getElementById('paymentModal')).show(); });</script>@endif
@endsection
