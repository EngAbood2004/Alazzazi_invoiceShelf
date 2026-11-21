@extends('layouts.app')
@section('title', __('Recurring invoice'))
@php($lines = old('items', $schedule->items ?: [['description' => '', 'quantity' => 1, 'unit_price' => '']]))
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Recurring invoices') }}</div><h1 class="page-title">{{ $schedule->exists ? __('Edit schedule') : __('New schedule') }}</h1></div>
    <a href="{{ route('recurring-invoices.index') }}" class="btn btn-light border">{{ __('Back') }}</a>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $schedule->exists ? route('recurring-invoices.update', $schedule) : route('recurring-invoices.store') }}">
    @csrf @if($schedule->exists) @method('PUT') @endif
    <div class="card mb-3"><div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label">{{ __('Customer') }}</label><select name="customer_id" class="form-select" required><option value="">—</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected((int) old('customer_id', $schedule->customer_id) === $c->id)>{{ $c->displayName() }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">{{ __('Frequency') }}</label><select name="frequency" class="form-select">@foreach(['weekly' => __('Weekly'), 'monthly' => __('Monthly'), 'quarterly' => __('Quarterly'), 'yearly' => __('Yearly')] as $k => $v)<option value="{{ $k }}" @selected(old('frequency', $schedule->frequency) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">{{ __('Next invoice') }}</label><input type="date" name="next_run_date" value="{{ old('next_run_date', $schedule->next_run_date?->format('Y-m-d')) }}" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">{{ __('Due after (days)') }}</label><input type="number" min="0" name="due_in_days" value="{{ old('due_in_days', $schedule->due_in_days) }}" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">{{ __('Currency') }}</label><select name="currency" class="form-select">@foreach(['USD','ILS','JOD','EUR'] as $c)<option @selected(old('currency', $schedule->currency) === $c)>{{ $c }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">{{ __('Stop on (optional)') }}</label><input type="date" name="ends_on" value="{{ old('ends_on', $schedule->ends_on?->format('Y-m-d')) }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Or after N invoices') }}</label><input type="number" min="1" name="max_runs" value="{{ old('max_runs', $schedule->max_runs) }}" class="form-control"></div>
        <div class="col-md-6 d-flex align-items-end gap-4">
            <div class="form-check form-switch"><input type="hidden" name="auto_send" value="0"><input class="form-check-input" type="checkbox" name="auto_send" value="1" id="auto_send" @checked(old('auto_send', $schedule->auto_send))><label class="form-check-label" for="auto_send">{{ __('Mark as sent automatically') }}</label></div>
            <div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $schedule->is_active))><label class="form-check-label" for="is_active">{{ __('Active') }}</label></div>
        </div>
    </div></div>
    <div class="card mb-3"><div class="card-header d-flex justify-content-between"><span class="fw-bold">{{ __('Items') }}</span><button type="button" class="btn btn-sm btn-outline-primary" onclick="addLine()"><i class="bi bi-plus-lg"></i> {{ __('Add line') }}</button></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>{{ __('Product') }}</th><th>{{ __('Description') }}</th><th style="width:110px">{{ __('Qty') }}</th><th style="width:140px">{{ __('Unit price') }}</th><th style="width:160px">{{ __('Tax') }}</th><th></th></tr></thead>
        <tbody id="lines">@foreach($lines as $i => $line)<tr class="line">
            <td><select name="items[{{ $i }}][product_id]" class="form-select form-select-sm" onchange="pick(this)"><option value="">—</option>@foreach($products as $p)<option value="{{ $p->id }}" data-price="{{ $p->selling_price }}" data-name="{{ $p->name }}" @selected((int) ($line['product_id'] ?? 0) === $p->id)>{{ $p->name }}</option>@endforeach</select></td>
            <td><input name="items[{{ $i }}][description]" value="{{ $line['description'] ?? '' }}" class="form-control form-control-sm" required></td>
            <td><input type="number" step="0.001" min="0.001" name="items[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" class="form-control form-control-sm" required></td>
            <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_price]" value="{{ $line['unit_price'] ?? '' }}" class="form-control form-control-sm" required></td>
            <td><select name="items[{{ $i }}][tax_id]" class="form-select form-select-sm"><option value="">{{ __('No tax') }}</option>@foreach($taxes as $t)<option value="{{ $t->id }}" @selected((int) ($line['tax_id'] ?? 0) === $t->id)>{{ $t->label() }}</option>@endforeach</select></td>
            <td><button type="button" class="btn btn-sm btn-light border" onclick="removeLine(this)"><i class="bi bi-x-lg"></i></button></td>
        </tr>@endforeach</tbody></table></div>
    </div>
    <div class="card mb-3"><div class="card-body"><label class="form-label">{{ __('Notes') }}</label><textarea name="notes" rows="2" class="form-control">{{ old('notes', $schedule->notes) }}</textarea></div><div class="card-footer bg-white text-end"><button class="btn btn-primary px-4">{{ __('Save') }}</button></div></div>
</form>
@endsection
@section('scripts')
<script>
const tbody = document.getElementById('lines');
function renumber() { tbody.querySelectorAll('tr').forEach((r, i) => r.querySelectorAll('[name]').forEach(el => el.name = el.name.replace(/items\[\d+\]/, `items[${i}]`))); }
function addLine() { const c = tbody.querySelector('tr').cloneNode(true); c.querySelectorAll('input').forEach(i => i.value = i.type === 'number' && i.name.includes('quantity') ? 1 : ''); c.querySelectorAll('select').forEach(s => s.selectedIndex = 0); tbody.appendChild(c); renumber(); }
function removeLine(btn) { if (tbody.querySelectorAll('tr').length > 1) { btn.closest('tr').remove(); renumber(); } }
function pick(sel) { const o = sel.selectedOptions[0], r = sel.closest('tr'); if (o?.value) { r.querySelector('[name$="[unit_price]"]').value = o.dataset.price; const d = r.querySelector('[name$="[description]"]'); if (!d.value) d.value = o.dataset.name; } }
</script>
@endsection
