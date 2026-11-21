@extends('layouts.app')

@section('title', __('Warehouse list'))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ __('Warehouses') }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('warehouses.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> {{ __('Add warehouse') }}
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white py-3">
        <form action="{{ route('warehouses.index') }}" method="GET" class="row g-3">
            <div class="col-auto">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search warehouses...') }}" value="{{ $search }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">{{ __('Search') }}</button>
                @if($search)
                    <a href="{{ route('warehouses.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">{{ __('No.') }}</th>
                        <th>{{ __('Warehouse code') }}</th>
                        <th>{{ __('Warehouse name') }}</th>
                        <th>{{ __('Address / location') }}</th>
                        <th width="100">{{ __('Status') }}</th>
                        <th width="150" class="text-center">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouses as $warehouse)
                    <tr>
                        <td>{{ ($warehouses->currentPage() - 1) * $warehouses->perPage() + $loop->iteration }}</td>
                        <td><code>{{ $warehouse->code }}</code></td>
                        <td class="fw-bold">{{ $warehouse->name }}</td>
                        <td>{{ $warehouse->address }}</td>
                        <td>
                            @if($warehouse->is_active)
                                <span class="badge bg-success">{{ __('Active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" onsubmit="return confirm(@json(__('Are you sure you want to delete this warehouse?')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">{{ __('No warehouses found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $warehouses->links() }}
    </div>
</div>
@endsection
