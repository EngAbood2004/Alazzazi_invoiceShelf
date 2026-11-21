@extends('layouts.app')

<<<<<<< HEAD
@section('title', __('Category list'))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ __('Categories') }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('categories.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> {{ __('Add category') }}
=======
@section('title', 'Daftar Kategori')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kategori</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('categories.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white py-3">
        <form action="{{ route('categories.index') }}" method="GET" class="row g-3">
            <div class="col-auto">
<<<<<<< HEAD
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search categories...') }}" value="{{ $search }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">{{ __('Search') }}</button>
=======
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari kategori..." value="{{ $search }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Cari</button>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                @if($search)
                    <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th>{{ __('Category name') }}</th>
                        <th>{{ __('Slug') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th width="100">{{ __('Status') }}</th>
                        <th width="150" class="text-center">{{ __('Actions') }}</th>
=======
                        <th width="50">No</th>
                        <th>Nama Kategori</th>
                        <th>Slug</th>
                        <th>Deskripsi</th>
                        <th width="100">Status</th>
                        <th width="150" class="text-center">Aksi</th>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td>{{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}</td>
                        <td class="fw-bold">{{ $category->name }}</td>
                        <td><code>{{ $category->slug }}</code></td>
                        <td>{{ Str::limit($category->description, 50) }}</td>
                        <td>
                            @if($category->is_active)
<<<<<<< HEAD
                                <span class="badge bg-success">{{ __('Active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('Inactive') }}</span>
=======
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Non-aktif</span>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group">
                                <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
<<<<<<< HEAD
                                <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm(@json(__('Are you sure you want to delete this category?')))">
=======
                                <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" {{ $category->products_count > 0 ? 'disabled' : '' }}>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
<<<<<<< HEAD
                        <td colspan="6" class="text-center py-4 text-muted">{{ __('No categories found.') }}</td>
=======
                        <td colspan="6" class="text-center py-4 text-muted">Tidak ada data kategori.</td>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $categories->links() }}
    </div>
</div>
@endsection
