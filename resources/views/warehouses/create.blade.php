@extends('layouts.app')

<<<<<<< HEAD
@section('title', __('Add warehouse'))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ __('Add warehouse') }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('warehouses.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
=======
@section('title', 'Tambah Gudang')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Gudang</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('warehouses.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('warehouses.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
<<<<<<< HEAD
                        <label for="code" class="form-label">{{ __('Warehouse code') }}</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required autofocus placeholder="{{ __('Example: WH-001') }}">
=======
                        <label for="code" class="form-label">Kode Gudang</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required autofocus placeholder="Contoh: GDG-001">
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
<<<<<<< HEAD
                        <label for="name" class="form-label">{{ __('Warehouse name') }}</label>
=======
                        <label for="name" class="form-label">Nama Gudang</label>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
<<<<<<< HEAD
                        <label for="address" class="form-label">{{ __('Address / location') }}</label>
=======
                        <label for="address" class="form-label">Lokasi / Alamat</label>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
<<<<<<< HEAD
                            <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">{{ __('Save warehouse') }}</button>
=======
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">Simpan Gudang</button>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
