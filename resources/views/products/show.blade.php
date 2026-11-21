@extends('layouts.app')

<<<<<<< HEAD
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
=======
@section('title', 'Detail Produk')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Detail Produk: {{ $product->name }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit Produk
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        </a>
    </div>
</div>

<div class="row">
<<<<<<< HEAD
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-white fw-bold">{{ __('Product information') }}</div>
=======
    <!-- Product Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-white fw-bold">Informasi Produk</div>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td width="120" class="text-muted">SKU</td>
                        <td class="fw-bold">{{ $product->sku }}</td>
                    </tr>
                    <tr>
<<<<<<< HEAD
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
=======
                        <td class="text-muted">Kategori</td>
                        <td>{{ $product->category?->name ?? 'بدون تصنيف' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Supplier</td>
                        <td>{{ $product->supplier?->name ?? 'بدون مورد' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Satuan</td>
                        <td>{{ $product->unit }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Harga Beli</td>
                        <td>Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Harga Jual</td>
                        <td class="text-primary fw-bold">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Stok Minimum</td>
                        <td>{{ $product->minimum_stock }} {{ $product->unit }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>
                            @if($product->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Non-aktif</span>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                            @endif
                        </td>
                    </tr>
                </table>
                <hr>
                <div class="mt-3">
<<<<<<< HEAD
                    <h6 class="text-muted small text-uppercase">{{ __('Description') }}</h6>
                    <p class="mb-0">{{ $product->description ?: __('No description.') }}</p>
=======
                    <h6 class="text-muted small text-uppercase">Deskripsi</h6>
                    <p class="mb-0">{{ $product->description ?: 'Tidak ada deskripsi.' }}</p>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                </div>
            </div>
        </div>
    </div>

<<<<<<< HEAD
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">{{ __('Stock by warehouse') }}</span>
                <span class="badge {{ $product->isLowStock() ? 'bg-danger' : 'bg-primary' }} fs-6">
                    {{ __('Total stock') }}: {{ $product->totalStock() }} {{ $product->unit }}
=======
    <!-- Stock Levels -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Posisi Stok per Gudang</span>
                <span class="badge {{ $product->isLowStock() ? 'bg-danger' : 'bg-primary' }} fs-6">
                    Total Stok: {{ $product->totalStock() }} {{ $product->unit }}
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
<<<<<<< HEAD
                                <th>{{ __('Warehouse') }}</th>
                                <th>{{ __('Location') }}</th>
                                <th class="text-center">{{ __('Stock quantity') }}</th>
                                <th class="text-center">{{ __('Actions') }}</th>
=======
                                <th>Gudang</th>
                                <th>Lokasi</th>
                                <th class="text-center">Jumlah Stok</th>
                                <th class="text-center">Aksi</th>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
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
<<<<<<< HEAD
                                        {{ __('Update stock') }}
=======
                                        Update Stok
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
<<<<<<< HEAD
                                <td colspan="4" class="text-center py-4 text-muted">{{ __('No stock in any warehouse yet.') }}</td>
=======
                                <td colspan="4" class="text-center py-4 text-muted">Belum ada stok di gudang manapun.</td>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('stock-movements.create', ['product_id' => $product->id]) }}" class="btn btn-sm btn-primary">
<<<<<<< HEAD
                    <i class="bi bi-plus-circle me-1"></i> {{ __('Add stock to a new warehouse') }}
=======
                    <i class="bi bi-plus-circle me-1"></i> Tambah ke Gudang Baru
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                </a>
            </div>
        </div>

<<<<<<< HEAD
        <div class="card">
            <div class="card-header bg-white fw-bold">{{ __('Recent stock movements') }}</div>
=======
        <!-- Recent Movements -->
        <div class="card">
            <div class="card-header bg-white fw-bold">Riwayat Pergerakan Stok Terakhir</div>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size: 0.875rem;">
                        <thead class="table-light">
                            <tr>
<<<<<<< HEAD
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Warehouse') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th class="text-end">{{ __('Quantity') }}</th>
                                <th>{{ __('Notes') }}</th>
                                <th>{{ __('Created by') }}</th>
=======
                                <th>Tanggal</th>
                                <th>Gudang</th>
                                <th>Tipe</th>
                                <th class="text-end">Jumlah</th>
                                <th>Keterangan</th>
                                <th>Oleh</th>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentMovements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $movement->warehouse?->name ?? 'مستودع محذوف' }}</td>
                                <td>
                                    @if($movement->type === 'in')
<<<<<<< HEAD
                                        <span class="badge bg-success">{{ __('Stock in') }}</span>
                                    @elseif($movement->type === 'out')
                                        <span class="badge bg-danger">{{ __('Stock out') }}</span>
                                    @else
                                        <span class="badge bg-warning">{{ __('Adjustment') }}</span>
=======
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
                                <td><small>{{ $movement->reference_number ?: '-' }} - {{ $movement->notes }}</small></td>
<<<<<<< HEAD
                                <td>{{ $movement->creator?->name ?? __('System') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">{{ __('No stock movements yet.') }}</td>
=======
                                <td>{{ $movement->creator?->name ?? 'النظام' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat pergerakan.</td>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
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
