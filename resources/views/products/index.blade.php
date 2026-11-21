@extends('layouts.app')

<<<<<<< HEAD
@section('title', __('Product list'))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ __('Products') }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> {{ __('Add product') }}
=======
@section('title', 'Daftar Produk')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Produk</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white py-3">
<<<<<<< HEAD
        <h6 class="mb-3"><i class="bi bi-filter me-1"></i> {{ __('Filter products') }}</h6>
        <form action="{{ route('products.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search name or SKU...') }}" value="{{ $search }}">
            </div>
            <div class="col-md-2">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">{{ __('All categories') }}</option>
=======
        <h6 class="mb-3"><i class="bi bi-filter me-1"></i> Filter Produk</h6>
        <form action="{{ route('products.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama atau SKU..." value="{{ $search }}">
            </div>
            <div class="col-md-2">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="supplier_id" class="form-select form-select-sm">
<<<<<<< HEAD
                    <option value="">{{ __('All suppliers') }}</option>
=======
                    <option value="">Semua Supplier</option>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ $supplierId == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="low_stock" class="form-select form-select-sm">
<<<<<<< HEAD
                    <option value="">{{ __('All stock levels') }}</option>
                    <option value="1" {{ $lowStock ? 'selected' : '' }}>{{ __('Low stock') }}</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">{{ __('Filter') }}</button>
=======
                    <option value="">Semua Stok</option>
                    <option value="1" {{ $lowStock ? 'selected' : '' }}>Stok Rendah</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                @if($search || $categoryId || $supplierId || $lowStock)
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th width="50">{{ __('No.') }}</th>
                        <th>SKU</th>
                        <th>{{ __('Product name') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th class="text-end">{{ __('Sale price') }}</th>
                        <th class="text-center">{{ __('Stock') }}</th>
                        <th width="100">{{ __('Status') }}</th>
                        <th width="150" class="text-center">{{ __('Actions') }}</th>
=======
                        <th width="50">No</th>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="text-end">Harga Jual</th>
                        <th class="text-center">Stok</th>
                        <th width="100">Status</th>
                        <th width="150" class="text-center">Aksi</th>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</td>
                        <td><code>{{ $product->sku }}</code></td>
                        <td>
                            <div class="fw-bold">{{ $product->name }}</div>
                            <small class="text-muted">{{ $product->supplier->name ?? '-' }}</small>
                        </td>
                        <td>{{ $product->category->name ?? '-' }}</td>
<<<<<<< HEAD
                        <td class="text-end">{{ number_format($product->selling_price, 2) }}</td>
=======
                        <td class="text-end">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                        <td class="text-center">
                            @php $totalStock = $product->totalStock(); @endphp
                            <span class="badge {{ $product->isLowStock() ? 'bg-danger' : 'bg-info' }}">
                                {{ $totalStock }} {{ $product->unit }}
                            </span>
                        </td>
                        <td>
                            @if($product->is_active)
<<<<<<< HEAD
                                <span class="badge bg-success">{{ __('Active') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('Inactive') }}</span>
=======
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Non-aktif</span>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
<<<<<<< HEAD
                                <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm(@json(__('Are you sure you want to delete this product?')))">
=======
                                <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
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
<<<<<<< HEAD
                        <td colspan="8" class="text-center py-4 text-muted">{{ __('No products found.') }}</td>
=======
                        <td colspan="8" class="text-center py-4 text-muted">Tidak ada data produk.</td>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $products->links() }}
    </div>
</div>
@endsection
