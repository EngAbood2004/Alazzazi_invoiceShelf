@extends('layouts.app')

<<<<<<< HEAD
@section('title', __('Supplier list'))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ __('Suppliers') }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('suppliers.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> {{ __('Add supplier') }}
=======
@section('title', 'Daftar Supplier')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Supplier</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('suppliers.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Tambah Supplier
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white py-3">
        <form action="{{ route('suppliers.index') }}" method="GET" class="row g-3">
            <div class="col-auto">
<<<<<<< HEAD
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Search suppliers...') }}" value="{{ $search }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">{{ __('Search') }}</button>
=======
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari supplier..." value="{{ $search }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Cari</button>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                @if($search)
                    <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th>{{ __('Company name') }}</th>
                        <th>{{ __('Contact person') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th width="100">{{ __('Status') }}</th>
                        <th width="150" class="text-center">{{ __('Actions') }}</th>
=======
                        <th width="50">No</th>
                        <th>Nama Perusahaan</th>
                        <th>Kontak Personal</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th width="100">Status</th>
                        <th width="150" class="text-center">Aksi</th>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        <td>{{ ($suppliers->currentPage() - 1) * $suppliers->perPage() + $loop->iteration }}</td>
                        <td class="fw-bold">{{ $supplier->name }}</td>
                        <td>{{ $supplier->contact_name }}</td>
                        <td>{{ $supplier->email }}</td>
                        <td>{{ $supplier->phone }}</td>
                        <td>
                            @if($supplier->is_active)
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
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
<<<<<<< HEAD
                                <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm(@json(__('Are you sure you want to delete this supplier?')))">
=======
                                <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?')">
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
                        <td colspan="7" class="text-center py-4 text-muted">{{ __('No suppliers found.') }}</td>
=======
                        <td colspan="7" class="text-center py-4 text-muted">Tidak ada data supplier.</td>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $suppliers->links() }}
    </div>
</div>
@endsection
