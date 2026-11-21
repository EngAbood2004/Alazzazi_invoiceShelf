@extends('layouts.app')
@section('title', __('Invoicing settings'))
@section('content')
<div class="mb-4"><div class="eyebrow">{{ __('Administration') }}</div><h1 class="page-title">{{ __('Invoicing settings') }}</h1></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="row g-3">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('invoicing-settings.update') }}" class="card">@csrf @method('PUT')
            <div class="card-header fw-bold">{{ __('Company and documents') }}</div>
            <div class="card-body row g-3">
                <div class="col-md-6"><label class="form-label">{{ __('Company name') }}</label><input name="company_name" value="{{ old('company_name', $settings['company_name']) }}" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">{{ __('Email') }}</label><input type="email" name="company_email" value="{{ old('company_email', $settings['company_email']) }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">{{ __('Phone') }}</label><input name="company_phone" value="{{ old('company_phone', $settings['company_phone']) }}" class="form-control" dir="ltr"></div>
                <div class="col-md-6"><label class="form-label">{{ __('Tax number') }}</label><input name="company_tax_number" value="{{ old('company_tax_number', $settings['company_tax_number']) }}" class="form-control"></div>
                <div class="col-12"><label class="form-label">{{ __('Address') }}</label><textarea name="company_address" rows="2" class="form-control">{{ old('company_address', $settings['company_address']) }}</textarea></div>
                <div class="col-md-3"><label class="form-label">{{ __('Default currency') }}</label><select name="currency" class="form-select">@foreach(['USD','ILS','JOD','EUR','EGP','SAR'] as $c)<option @selected(old('currency', $settings['currency']) === $c)>{{ $c }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">{{ __('Invoice prefix') }}</label><input name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix']) }}" class="form-control" dir="ltr"></div>
                <div class="col-md-3"><label class="form-label">{{ __('Estimate prefix') }}</label><input name="estimate_prefix" value="{{ old('estimate_prefix', $settings['estimate_prefix']) }}" class="form-control" dir="ltr"></div>
                <div class="col-md-3"><label class="form-label">{{ __('Due after (days)') }}</label><input type="number" min="0" name="invoice_due_days" value="{{ old('invoice_due_days', $settings['invoice_due_days']) }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">{{ __('Estimate valid (days)') }}</label><input type="number" min="1" name="estimate_valid_days" value="{{ old('estimate_valid_days', $settings['estimate_valid_days']) }}" class="form-control"></div>
                <div class="col-md-9"><label class="form-label">{{ __('Default terms') }}</label><input name="invoice_terms" value="{{ old('invoice_terms', $settings['invoice_terms']) }}" class="form-control"></div>
            </div>
            <div class="card-header fw-bold border-top">{{ __('Overdue reminders') }}</div>
            <div class="card-body row g-3">
                <div class="col-md-6"><label class="form-label">{{ __('Send on these days after the due date') }}</label><input name="reminder_days" value="{{ old('reminder_days', $settings['reminder_days']) }}" class="form-control" dir="ltr" placeholder="1,7,14,30"><div class="form-text">{{ __('Each step is sent once per channel, every day at 09:00.') }}</div></div>
                <div class="col-md-6"><label class="form-label d-block">{{ __('Channels') }}</label>
                    @foreach(['email' => __('Email'), 'whatsapp' => 'WhatsApp'] as $key => $label)
                        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="reminder_channels[]" value="{{ $key }}" id="ch_{{ $key }}" @checked(in_array($key, explode(',', $settings['reminder_channels'])))><label class="form-check-label" for="ch_{{ $key }}">{{ $label }}</label></div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-white text-end"><button class="btn btn-primary px-4">{{ __('Save') }}</button></div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-header fw-bold">{{ __('Taxes') }}</div><ul class="list-group list-group-flush">
            @foreach($taxes as $tax)<li class="list-group-item d-flex justify-content-between align-items-center"><span class="{{ $tax->is_active ? '' : 'text-muted text-decoration-line-through' }}">{{ $tax->label() }}</span><form method="POST" action="{{ route('taxes.toggle', $tax) }}">@csrf<button class="btn btn-sm btn-light border">{{ $tax->is_active ? __('Disable') : __('Enable') }}</button></form></li>@endforeach
        </ul><form method="POST" action="{{ route('taxes.store') }}" class="card-body d-flex gap-2">@csrf<input name="name" class="form-control form-control-sm" placeholder="{{ __('Name, e.g. VAT') }}" required><input type="number" step="0.001" min="0" max="100" name="rate" class="form-control form-control-sm" style="width:90px" placeholder="%" required><button class="btn btn-sm btn-primary">{{ __('Add') }}</button></form></div>
        <div class="card"><div class="card-header fw-bold">{{ __('Integrations (.env)') }}</div><ul class="list-group list-group-flush small">
            @foreach($integrations as $name => $ok)<li class="list-group-item d-flex justify-content-between"><span>{{ $name }}</span>@if($ok)<span class="text-success"><i class="bi bi-check-circle-fill"></i> {{ __('Connected') }}</span>@else<span class="text-muted"><i class="bi bi-dash-circle"></i> {{ __('Not configured') }}</span>@endif</li>@endforeach
        </ul></div>
    </div>
</div>
@endsection
