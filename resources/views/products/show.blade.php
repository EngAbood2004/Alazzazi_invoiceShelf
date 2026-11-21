@extends('layouts.app')

@section('title', __('Product details'))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ __('Product details') }}: {{ $product->name }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
        </a>
        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i> {{ __('Edit product') }}
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-white fw-bold">{{ __('Product information') }}</div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td width="120" class="text-muted">SKU</td>
                        <td class="fw-bold">{{ $product->sku }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('Category') }}</td>
                        <td>{{ $product->category?->name ?? __('No category') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('Supplier') }}</td>
                        <td>{{ $product->supplier?->name ?? __('No supplier') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('Unit') }}</td>
                        <td>{{ $product->unit }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('Purchase price') }}</td>
                        <td>{{ number_format($product->purchase_price, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('Sale price') }}</td>
                        <td class="text-primary fw-bold">{{ number_format($product->selling_price, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('Minimum stock') }}</td>
                        <td>{{ $product->minimum_stock }} {{ $product->unit }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('Status') }}</td>
                        <td>
                            @if($product->is_active)
                                <span class="badge bg-success">{{ __('Active') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                </table>
                <hr>
                <div class="mt-3">
                    <h6 class="text-muted small text-uppercase">{{ __('Description') }}</h6>
                    <p class="mb-0">{{ $product->description ?: __('No description.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">{{ __('Stock by warehouse') }}</span>
                <span class="badge {{ $product->isLowStock() ? 'bg-danger' : 'bg-primary' }} fs-6">
                    {{ __('Total stock') }}: {{ $product->totalStock() }} {{ $product->unit }}
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Warehouse') }}</th>
                                <th>{{ __('Location') }}</th>
                                <th class="text-center">{{ __('Stock quantity') }}</th>
                                <th class="text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->stocks as $stock)
                            <tr>
                                <td class="fw-bold">{{ $stock->warehouse?->name ?? 'مستودع محذوف' }}</td>
                                <td>{{ $stock->warehouse?->location ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info fs-6">{{ $stock->quantity }} {{ $product->unit }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('stock-movements.create', ['product_id' => $product->id, 'warehouse_id' => $stock->warehouse_id]) }}" class="btn btn-sm btn-outline-primary">
                                        {{ __('Update stock') }}
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">{{ __('No stock in any warehouse yet.') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('stock-movements.create', ['product_id' => $product->id]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> {{ __('Add stock to a new warehouse') }}
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white fw-bold">{{ __('Recent stock movements') }}</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size: 0.875rem;">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Warehouse') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th class="text-end">{{ __('Quantity') }}</th>
                                <th>{{ __('Notes') }}</th>
                                <th>{{ __('Created by') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentMovements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $movement->warehouse?->name ?? 'مستودع محذوف' }}</td>
                                <td>
                                    @if($movement->type === 'in')
                                        <span class="badge bg-success">{{ __('Stock in') }}</span>
                                    @elseif($movement->type === 'out')
                                        <span class="badge bg-danger">{{ __('Stock out') }}</span>
                                    @else
                                        <span class="badge bg-warning">{{ __('Adjustment') }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold {{ $movement->type === 'in' ? 'text-success' : ($movement->type === 'out' ? 'text-danger' : '') }}">
                                    {{ $movement->type === 'out' ? '-' : '+' }}{{ $movement->quantity }}
                                </td>
                                <td><small>{{ $movement->reference_number ?: '-' }} - {{ $movement->notes }}</small></td>
                                <td>{{ $movement->creator?->name ?? __('System') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">{{ __('No stock movements yet.') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
