@extends('layouts.app')

<<<<<<< HEAD
@section('title', __('Stock movement history'))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ __('Stock movement history') }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('stock-movements.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> {{ __('Record a stock movement') }}
=======
@section('title', 'Riwayat Pergerakan Stok')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Riwayat Pergerakan Stok</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('stock-movements.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Catat Pergerakan Baru
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white py-3">
<<<<<<< HEAD
        <h6 class="mb-3"><i class="bi bi-filter me-1"></i> {{ __('Filter history') }}</h6>
        <form action="{{ route('stock-movements.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">{{ __('All products') }}</option>
=======
        <h6 class="mb-3"><i class="bi bi-filter me-1"></i> Filter Riwayat</h6>
        <form action="{{ route('stock-movements.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">Semua Produk</option>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ $productId == $product->id ? 'selected' : '' }}>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="warehouse_id" class="form-select form-select-sm">
<<<<<<< HEAD
                    <option value="">{{ __('All warehouses') }}</option>
=======
                    <option value="">Semua Gudang</option>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $warehouseId == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select form-select-sm">
<<<<<<< HEAD
                    <option value="">{{ __('All types') }}</option>
                    <option value="in" {{ $type === 'in' ? 'selected' : '' }}>{{ __('Stock in') }}</option>
                    <option value="out" {{ $type === 'out' ? 'selected' : '' }}>{{ __('Stock out') }}</option>
                    <option value="adjustment" {{ $type === 'adjustment' ? 'selected' : '' }}>{{ __('Adjustment') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}" aria-label="{{ __('From date') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}" aria-label="{{ __('To date') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">{{ __('Filter') }}</button>
=======
                    <option value="">Semua Tipe</option>
                    <option value="in" {{ $type === 'in' ? 'selected' : '' }}>Masuk</option>
                    <option value="out" {{ $type === 'out' ? 'selected' : '' }}>Keluar</option>
                    <option value="adjustment" {{ $type === 'adjustment' ? 'selected' : '' }}>Penyesuaian</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}" placeholder="Dari Tanggal">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}" placeholder="Sampai Tanggal">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                @if($productId || $warehouseId || $type || $dateFrom || $dateTo)
                    <a href="{{ route('stock-movements.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
<<<<<<< HEAD
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Warehouse') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th class="text-end">{{ __('Quantity') }}</th>
                        <th>{{ __('Reference / notes') }}</th>
                        <th>{{ __('Created by') }}</th>
=======
                        <th>Tanggal</th>
                        <th>Produk</th>
                        <th>Gudang</th>
                        <th>Tipe</th>
                        <th class="text-end">Jumlah</th>
                        <th>Ref / Keterangan</th>
                        <th>Oleh</th>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->movement_date->format('d/m/Y') }}</td>
                        <td>
<<<<<<< HEAD
                            <div class="fw-bold">{{ $movement->product?->name ?? '—' }}</div>
                            <small class="text-muted">{{ $movement->product?->sku ?? '' }}</small>
                        </td>
                        <td>{{ $movement->warehouse?->name ?? '—' }}</td>
                        <td>
                            @if($movement->type === 'in')
                                <span class="badge bg-success">{{ __('Stock in') }}</span>
                            @elseif($movement->type === 'out')
                                <span class="badge bg-danger">{{ __('Stock out') }}</span>
                            @else
                                <span class="badge bg-warning">{{ __('Adjustment') }}</span>
=======
                            <div class="fw-bold">{{ $movement->product->name }}</div>
                            <small class="text-muted">{{ $movement->product->sku }}</small>
                        </td>
                        <td>{{ $movement->warehouse->name }}</td>
                        <td>
                            @if($movement->type === 'in')
                                <span class="badge bg-success">Masuk</span>
                            @elseif($movement->type === 'out')
                                <span class="badge bg-danger">Keluar</span>
                            @else
                                <span class="badge bg-warning">Penyesuaian</span>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $movement->type === 'in' ? 'text-success' : ($movement->type === 'out' ? 'text-danger' : '') }}">
                            {{ $movement->type === 'out' ? '-' : '+' }}{{ $movement->quantity }}
                        </td>
                        <td>
                            <div>{{ $movement->reference_number ?: '-' }}</div>
                            <small class="text-muted">{{ Str::limit($movement->notes, 30) }}</small>
                        </td>
<<<<<<< HEAD
                        <td>{{ $movement->creator?->name ?? __('System') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">{{ __('No stock movements found.') }}</td>
=======
                        <td>{{ $movement->creator->name }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Tidak ada riwayat pergerakan stok.</td>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $movements->links() }}
    </div>
</div>
@endsection
