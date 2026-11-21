@extends('layouts.app')
@section('title', $customer->exists ? __('Edit customer') : __('New customer'))
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Customers') }}</div><h1 class="page-title">{{ $customer->exists ? __('Edit customer') : __('New customer') }}</h1></div>
    <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}" class="btn btn-light border">{{ __('Back') }}</a>
</div>
<form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="card"><div class="card-body">
    @csrf @if($customer->exists) @method('PUT') @endif
    <div class="row g-3">
        @foreach([
            ['name', __('Name'), 'text', true], ['company_name', __('Company'), 'text', false],
            ['email', __('Email'), 'email', false], ['phone', __('Phone'), 'text', false],
            ['whatsapp', __('WhatsApp number (with country code)'), 'text', false], ['tax_number', __('Tax number'), 'text', false],
            ['address', __('Address'), 'text', false], ['city', __('City'), 'text', false], ['country', __('Country'), 'text', false],
        ] as [$field, $label, $type, $required])
            <div class="col-md-4">
                <label class="form-label" for="{{ $field }}">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field, $customer->$field) }}" class="form-control @error($field) is-invalid @enderror" @required($required) @if(in_array($field, ['phone','whatsapp'])) dir="ltr" placeholder="+970 59 000 0000" @endif>
                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
        <div class="col-md-4">
            <label class="form-label" for="currency">{{ __('Currency') }}</label>
            <select id="currency" name="currency" class="form-select">@foreach(['USD','ILS','JOD','EUR','EGP','SAR'] as $c)<option @selected(old('currency', $customer->currency) === $c)>{{ $c }}</option>@endforeach</select>
        </div>
        <div class="col-12"><label class="form-label" for="notes">{{ __('Notes') }}</label><textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes', $customer->notes) }}</textarea></div>
        <div class="col-12"><div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $customer->is_active ?? true))><label class="form-check-label" for="is_active">{{ __('Active') }}</label></div></div>
    </div>
</div><div class="card-footer bg-white text-end"><button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i>{{ __('Save') }}</button></div></form>
@endsection
