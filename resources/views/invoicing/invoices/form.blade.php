@extends('layouts.app')
@php
    $isInvoice = $type === 'invoice';
    $routeBase = $isInvoice ? 'invoices' : 'estimates';
    $title = $document->exists
        ? ($isInvoice ? __('Edit invoice :number', ['number' => $document->number]) : __('Edit estimate :number', ['number' => $document->number]))
        : ($isInvoice ? __('New invoice') : __('New estimate'));
    $lines = old('items', $document->exists ? $document->items->map(fn ($i) => $i->only(['product_id','tax_id','description','quantity','unit_price']))->all() : [['description' => '', 'quantity' => 1, 'unit_price' => '']]);
@endphp
@section('title', $title)
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ $isInvoice ? __('Invoices') : __('Estimates') }}</div><h1 class="page-title">{{ $title }}</h1></div>
    <a href="{{ $document->exists ? route($routeBase.'.show', $document) : route($routeBase.'.index') }}" class="btn btn-light border">{{ __('Back') }}</a>
</div>

@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ $document->exists ? route($routeBase.'.update', $document) : route($routeBase.'.store') }}" id="documentForm">
    @csrf @if($document->exists) @method('PUT') @endif
    <div class="card mb-3"><div class="card-body"><div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="customer_id">{{ __('Customer') }} <span class="text-danger">*</span></label>
            <select id="customer_id" name="customer_id" class="form-select" required>
                <option value="">{{ __('Choose a customer') }}</option>
                @foreach($customers as $customer)<option value="{{ $customer->id }}" data-currency="{{ $customer->currency }}" @selected((int) old('customer_id', $document->customer_id) === $customer->id)>{{ $customer->displayName() }}</option>@endforeach
            </select>
            <a href="{{ route('customers.create') }}" class="small text-decoration-none" target="_blank"><i class="bi bi-plus"></i> {{ __('New customer') }}</a>
        </div>
        <div class="col-md-2"><label class="form-label" for="issue_date">{{ __('Date') }}</label><input type="date" id="issue_date" name="issue_date" class="form-control" value="{{ old('issue_date', $document->issue_date?->format('Y-m-d')) }}" required></div>
        @if($isInvoice)
            <div class="col-md-2"><label class="form-label" for="due_date">{{ __('Due date') }}</label><input type="date" id="due_date" name="due_date" class="form-control" value="{{ old('due_date', $document->due_date?->format('Y-m-d')) }}"></div>
        @else
            <div class="col-md-2"><label class="form-label" for="expiry_date">{{ __('Valid until') }}</label><input type="date" id="expiry_date" name="expiry_date" class="form-control" value="{{ old('expiry_date', $document->expiry_date?->format('Y-m-d')) }}"></div>
        @endif
        <div class="col-md-2"><label class="form-label" for="currency">{{ __('Currency') }}</label><select id="currency" name="currency" class="form-select">@foreach(['USD','ILS','JOD','EUR','EGP','SAR'] as $c)<option @selected(old('currency', $document->currency) === $c)>{{ $c }}</option>@endforeach</select></div>
        @if($isInvoice)
            <div class="col-md-2"><label class="form-label" for="warehouse_id">{{ __('Deduct stock from') }}</label><select id="warehouse_id" name="warehouse_id" class="form-select"><option value="">{{ __('Do not deduct') }}</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected((int) old('warehouse_id', $document->warehouse_id) === $w->id)>{{ $w->name }}</option>@endforeach</select></div>
        @endif
    </div></div></div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center"><span class="fw-bold">{{ __('Items') }}</span><button type="button" class="btn btn-sm btn-outline-primary" id="addLine"><i class="bi bi-plus-lg"></i> {{ __('Add line') }}</button></div>
        <div class="table-responsive"><table class="table mb-0 align-middle" id="linesTable">
            <thead><tr><th style="min-width:180px">{{ __('Product') }}</th><th style="min-width:220px">{{ __('Description') }}</th><th style="width:110px">{{ __('Qty') }}</th><th style="width:140px">{{ __('Unit price') }}</th><th style="width:160px">{{ __('Tax') }}</th><th style="width:130px" class="text-end">{{ __('Amount') }}</th><th style="width:50px"></th></tr></thead>
            <tbody>
            @foreach($lines as $i => $line)
                <tr class="line">
                    <td><select name="items[{{ $i }}][product_id]" class="form-select form-select-sm product"><option value="">—</option>@foreach($products as $p)<option value="{{ $p->id }}" data-price="{{ $p->selling_price }}" data-name="{{ $p->name }}" @selected((int) ($line['product_id'] ?? 0) === $p->id)>{{ $p->name }} ({{ $p->sku }})</option>@endforeach</select></td>
                    <td><input name="items[{{ $i }}][description]" class="form-control form-control-sm description" value="{{ $line['description'] ?? '' }}" required></td>
                    <td><input name="items[{{ $i }}][quantity]" type="number" step="0.001" min="0.001" class="form-control form-control-sm qty" value="{{ $line['quantity'] ?? 1 }}" required></td>
                    <td><input name="items[{{ $i }}][unit_price]" type="number" step="0.01" min="0" class="form-control form-control-sm price" value="{{ $line['unit_price'] ?? '' }}" required></td>
                    <td><select name="items[{{ $i }}][tax_id]" class="form-select form-select-sm tax"><option value="" data-rate="0">{{ __('No tax') }}</option>@foreach($taxes as $tax)<option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" @selected((int) ($line['tax_id'] ?? 0) === $tax->id)>{{ $tax->label() }}</option>@endforeach</select></td>
                    <td class="text-end fw-semibold amount">0.00</td>
                    <td><button type="button" class="btn btn-sm btn-light border remove" title="{{ __('Remove') }}"><i class="bi bi-x-lg"></i></button></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7"><div class="card h-100"><div class="card-body">
            <label class="form-label" for="notes">{{ __('Notes to the customer') }}</label><textarea id="notes" name="notes" rows="3" class="form-control mb-3">{{ old('notes', $document->notes) }}</textarea>
            <label class="form-label" for="terms">{{ __('Terms and conditions') }}</label><textarea id="terms" name="terms" rows="3" class="form-control">{{ old('terms', $document->terms) }}</textarea>
        </div></div></div>
        <div class="col-lg-5"><div class="card h-100"><div class="card-body">
            <div class="metric-row"><span class="text-muted">{{ __('Subtotal') }}</span><span id="subtotal" class="fw-semibold">0.00</span></div>
            <div class="metric-row align-items-center gap-2"><span class="text-muted">{{ __('Discount') }}</span>
                <div class="d-flex gap-1" style="max-width:220px"><input type="number" step="0.01" min="0" name="discount_value" id="discount_value" class="form-control form-control-sm" value="{{ old('discount_value', (float) $document->discount_value) }}"><select name="discount_type" id="discount_type" class="form-select form-select-sm" style="width:80px"><option value="fixed" @selected(old('discount_type', $document->discount_type) === 'fixed')>{{ __('Amount') }}</option><option value="percent" @selected(old('discount_type', $document->discount_type) === 'percent')>%</option></select></div>
                <span id="discount" class="text-danger">0.00</span></div>
            <div class="metric-row"><span class="text-muted">{{ __('Tax') }}</span><span id="taxTotal">0.00</span></div>
            <div class="metric-row fs-5"><span class="fw-bold">{{ __('Total') }}</span><span id="total" class="fw-bold text-primary">0.00</span></div>
        </div><div class="card-footer bg-white d-flex gap-2 justify-content-end flex-wrap">
            <button class="btn btn-light border" name="send" value="0"><i class="bi bi-save me-1"></i>{{ __('Save draft') }}</button>
            @if($isInvoice && (! $document->exists || $document->status === 'draft'))<button class="btn btn-primary" name="send" value="1"><i class="bi bi-send me-1"></i>{{ __('Save and send') }}</button>@endif
            @unless($isInvoice)<button class="btn btn-primary"><i class="bi bi-check2 me-1"></i>{{ __('Save') }}</button>@endunless
        </div></div></div>
    </div>
</form>
@endsection

@section('scripts')
<script>
(() => {
    const table = document.querySelector('#linesTable tbody');
    const fmt = (v) => (Math.round(v * 100) / 100).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const num = (el) => parseFloat(el?.value || 0) || 0;

    function recalc() {
        let subtotal = 0; const lines = [];
        table.querySelectorAll('tr.line').forEach((row) => {
            const sub = num(row.querySelector('.qty')) * num(row.querySelector('.price'));
            const rate = parseFloat(row.querySelector('.tax').selectedOptions[0]?.dataset.rate || 0);
            subtotal += sub; lines.push({ sub, rate, row });
        });
        const type = document.getElementById('discount_type').value;
        const value = num(document.getElementById('discount_value'));
        const discount = type === 'percent' ? subtotal * Math.min(value, 100) / 100 : Math.min(value, subtotal);
        const ratio = subtotal > 0 ? 1 - discount / subtotal : 1;
        let tax = 0;
        lines.forEach(({ sub, rate, row }) => { const t = sub * ratio * rate / 100; tax += t; row.querySelector('.amount').textContent = fmt(sub + t); });
        document.getElementById('subtotal').textContent = fmt(subtotal);
        document.getElementById('discount').textContent = discount > 0 ? '- ' + fmt(discount) : '0.00';
        document.getElementById('taxTotal').textContent = fmt(tax);
        document.getElementById('total').textContent = fmt(subtotal - discount + tax);
    }

    function renumber() {
        table.querySelectorAll('tr.line').forEach((row, i) => row.querySelectorAll('[name]').forEach((el) => el.name = el.name.replace(/items\[\d+\]/, `items[${i}]`)));
    }

    document.getElementById('addLine').addEventListener('click', () => {
        const clone = table.querySelector('tr.line').cloneNode(true);
        clone.querySelectorAll('input').forEach((el) => el.value = el.classList.contains('qty') ? 1 : '');
        clone.querySelectorAll('select').forEach((el) => el.selectedIndex = 0);
        table.appendChild(clone); renumber(); recalc();
        clone.querySelector('.description').focus();
    });

    table.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove');
        if (!btn) return;
        if (table.querySelectorAll('tr.line').length > 1) { btn.closest('tr').remove(); renumber(); recalc(); }
    });

    table.addEventListener('change', (e) => {
        if (e.target.classList.contains('product')) {
            const opt = e.target.selectedOptions[0]; const row = e.target.closest('tr');
            if (opt?.value) { row.querySelector('.price').value = opt.dataset.price; if (!row.querySelector('.description').value) row.querySelector('.description').value = opt.dataset.name; }
        }
        recalc();
    });
    table.addEventListener('input', recalc);
    ['discount_type', 'discount_value'].forEach((id) => document.getElementById(id).addEventListener('input', recalc));

    document.getElementById('customer_id').addEventListener('change', (e) => {
        const cur = e.target.selectedOptions[0]?.dataset.currency; if (cur) document.getElementById('currency').value = cur;
    });
    recalc();
})();
</script>
@endsection
