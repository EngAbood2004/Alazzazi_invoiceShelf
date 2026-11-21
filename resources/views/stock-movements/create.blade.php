@extends('layouts.app')

<<<<<<< HEAD
@section('title', __('Record a stock movement'))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ __('Record a stock movement') }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('stock-movements.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> {{ __('Back to history') }}
=======
@section('title', 'Catat Pergerakan Stok')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Catat Pergerakan Stok</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('stock-movements.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Riwayat
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('stock-movements.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
<<<<<<< HEAD
                            <label for="product_id" class="form-label">{{ __('Product') }}</label>
                            <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">{{ __('Choose a product') }}</option>
=======
                            <label for="product_id" class="form-label">Produk</label>
                            <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Produk --</option>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id', request('product_id')) == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }} ({{ $product->sku }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
<<<<<<< HEAD
                            <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                            <select name="warehouse_id" id="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
                                <option value="">{{ __('Choose a warehouse') }}</option>
=======
                            <label for="warehouse_id" class="form-label">Gudang</label>
                            <select name="warehouse_id" id="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Gudang --</option>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('warehouse_id', request('warehouse_id')) == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
<<<<<<< HEAD
                            <label for="type" class="form-label">{{ __('Movement type') }}</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="in" {{ old('type') == 'in' ? 'selected' : '' }}>{{ __('Stock in (+)') }}</option>
                                <option value="out" {{ old('type') == 'out' ? 'selected' : '' }}>{{ __('Stock out (-)') }}</option>
                                <option value="adjustment" {{ old('type') == 'adjustment' ? 'selected' : '' }}>{{ __('Adjustment') }}</option>
=======
                            <label for="type" class="form-label">Tipe Pergerakan</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="in" {{ old('type') == 'in' ? 'selected' : '' }}>Stok Masuk (+)</option>
                                <option value="out" {{ old('type') == 'out' ? 'selected' : '' }}>Stok Keluar (-)</option>
                                <option value="adjustment" {{ old('type') == 'adjustment' ? 'selected' : '' }}>Penyesuaian</option>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
<<<<<<< HEAD
                            <label for="quantity" class="form-label">{{ __('Quantity') }}</label>
=======
                            <label for="quantity" class="form-label">Jumlah</label>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                            <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" value="{{ old('quantity') }}" required min="1">
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
<<<<<<< HEAD
                            <label for="movement_date" class="form-label">{{ __('Date') }}</label>
=======
                            <label for="movement_date" class="form-label">Tanggal</label>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                            <input type="date" class="form-control @error('movement_date') is-invalid @enderror" id="movement_date" name="movement_date" value="{{ old('movement_date', date('Y-m-d')) }}" required>
                            @error('movement_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
<<<<<<< HEAD
                        <label for="reference_number" class="form-label">{{ __('Reference number (optional)') }}</label>
                        <input type="text" class="form-control @error('reference_number') is-invalid @enderror" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" placeholder="{{ __('Example: PO-001, INV-2023-001') }}">
=======
                        <label for="reference_number" class="form-label">Nomor Referensi (Opsional)</label>
                        <input type="text" class="form-control @error('reference_number') is-invalid @enderror" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" placeholder="Contoh: PO-001, INV-2023-001">
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                        @error('reference_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
<<<<<<< HEAD
                        <label for="notes" class="form-label">{{ __('Notes') }}</label>
=======
                        <label for="notes" class="form-label">Catatan / Keterangan</label>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <div>
<<<<<<< HEAD
                            {{ __('Saved stock movements cannot be edited or deleted to preserve data integrity.') }}
=======
                            Pergerakan stok yang sudah disimpan tidak dapat diubah atau dihapus untuk menjaga integritas data.
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary px-4 py-2">
<<<<<<< HEAD
                        <i class="bi bi-check-circle me-1"></i> {{ __('Save movement') }}
=======
                        <i class="bi bi-check-circle me-1"></i> Simpan Pergerakan
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
<<<<<<< HEAD
            <div class="card-header bg-white fw-bold">{{ __('Guide') }}</div>
            <div class="card-body">
                <ul class="small text-muted ps-3">
                    <li class="mb-2"><strong>{{ __('Stock in') }}:</strong> {{ __('Use this when receiving goods from a supplier or accepting a return.') }}</li>
                    <li class="mb-2"><strong>{{ __('Stock out') }}:</strong> {{ __('Use this for sales, deliveries, or internal use.') }}</li>
                    <li class="mb-2"><strong>{{ __('Adjustment') }}:</strong> {{ __('Use this to correct stock after a stocktake. Enter the resulting quantity.') }}</li>
=======
            <div class="card-header bg-white fw-bold">Panduan</div>
            <div class="card-body">
                <ul class="small text-muted ps-3">
                    <li class="mb-2"><strong>Stok Masuk:</strong> Digunakan untuk penerimaan barang dari supplier atau pengembalian barang.</li>
                    <li class="mb-2"><strong>Stok Keluar:</strong> Digunakan untuk penjualan, pengiriman barang, atau pemakaian internal.</li>
                    <li class="mb-2"><strong>Penyesuaian:</strong> Digunakan jika ada selisih stok saat stock opname (bisa bernilai positif atau negatif di sistem, namun di form ini masukkan nilai positif dan sistem akan menyesuaikan berdasarkan tipe).</li>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
