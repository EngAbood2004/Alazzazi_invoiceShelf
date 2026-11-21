@extends('layouts.app')
@section('title', $expense->exists ? __('Edit expense') : __('New expense'))
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Expenses') }}</div><h1 class="page-title">{{ $expense->exists ? __('Edit expense') : __('New expense') }}</h1></div>
    <a href="{{ route('expenses.index') }}" class="btn btn-light border">{{ __('Back') }}</a>
</div>
<form method="POST" enctype="multipart/form-data" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}" class="card" style="max-width:820px"><div class="card-body"><div class="row g-3">
    @csrf @if($expense->exists) @method('PUT') @endif
    <div class="col-md-6"><label class="form-label">{{ __('Category') }}</label><select name="expense_category_id" class="form-select @error('expense_category_id') is-invalid @enderror"><option value="">{{ __('Choose or add below') }}</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected((int) old('expense_category_id', $expense->expense_category_id) === $c->id)>{{ $c->name }}</option>@endforeach</select>@error('expense_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label">{{ __('Or a new category') }}</label><input name="new_category" value="{{ old('new_category') }}" class="form-control" placeholder="{{ __('e.g. Rent, Salaries, Internet') }}"></div>
    <div class="col-md-4"><label class="form-label">{{ __('Amount') }}</label><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $expense->amount) }}" class="form-control @error('amount') is-invalid @enderror" required>@error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="col-md-4"><label class="form-label">{{ __('Currency') }}</label><select name="currency" class="form-select">@foreach(['USD','ILS','JOD','EUR','EGP','SAR'] as $c)<option @selected(old('currency', $expense->currency) === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">{{ __('Date') }}</label><input type="date" name="spent_on" value="{{ old('spent_on', $expense->spent_on?->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">{{ __('Supplier') }}</label><select name="supplier_id" class="form-select"><option value="">—</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" @selected((int) old('supplier_id', $expense->supplier_id) === $s->id)>{{ $s->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">{{ __('Payment method') }}</label><select name="payment_method_id" class="form-select"><option value="">—</option>@foreach($paymentMethods as $p)<option value="{{ $p->id }}" @selected((int) old('payment_method_id', $expense->payment_method_id) === $p->id)>{{ $p->name }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">{{ __('Description') }}</label><input name="description" value="{{ old('description', $expense->description) }}" class="form-control"></div>
    <div class="col-12"><label class="form-label">{{ __('Receipt (image or PDF, max 5 MB)') }}</label><input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="form-control @error('receipt') is-invalid @enderror">@error('receipt')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
</div></div><div class="card-footer bg-white text-end"><button class="btn btn-primary px-4">{{ __('Save') }}</button></div></form>
@endsection
