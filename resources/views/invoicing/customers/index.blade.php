@extends('layouts.app')
@section('title', __('Customers'))
@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
    <div><div class="eyebrow">{{ __('Invoicing') }}</div><h1 class="page-title">{{ __('Customers') }}</h1></div>
    <a href="{{ route('customers.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> {{ __('New customer') }}</a>
</div>
<div class="card">
    <div class="card-header">
        <form class="row g-2" method="GET">
            <div class="col-md-5"><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by name, company, email or phone') }}"></div>
            <div class="col-auto"><button class="btn btn-light border"><i class="bi bi-search"></i> {{ __('Search') }}</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>{{ __('Customer') }}</th><th>{{ __('Contact') }}</th><th>{{ __('Invoices') }}</th><th>{{ __('Balance due') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td><a href="{{ route('customers.show', $customer) }}" class="fw-bold text-decoration-none">{{ $customer->name }}</a>@if($customer->company_name)<div class="small text-muted">{{ $customer->company_name }}</div>@endif</td>
                    <td class="small">@if($customer->email)<div><i class="bi bi-envelope me-1"></i>{{ $customer->email }}</div>@endif @if($customer->phone)<div dir="ltr" class="text-start"><i class="bi bi-telephone me-1"></i>{{ $customer->phone }}</div>@endif</td>
                    <td>{{ $customer->invoices_count }}</td>
                    <td class="fw-semibold {{ ($customer->total_due ?? 0) > 0 ? 'text-danger' : '' }}">{{ \App\Support\Money::format($customer->total_due ?? 0, $customer->currency) }}</td>
                    <td>@if($customer->is_active)<span class="badge text-bg-success">{{ __('Active') }}</span>@else<span class="badge text-bg-secondary">{{ __('Inactive') }}</span>@endif</td>
                    <td class="text-end"><a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('New invoice') }}"><i class="bi bi-receipt"></i></a> <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-people fs-2 d-block mb-2"></i>{{ __('No customers yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())<div class="card-footer bg-white">{{ $customers->links() }}</div>@endif
</div>
@endsection
