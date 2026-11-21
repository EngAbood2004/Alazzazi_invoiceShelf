@extends('layouts.app')

<<<<<<< HEAD
@section('title', __('Add category'))

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ __('Add category') }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}
=======
@section('title', 'Tambah Kategori')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Kategori</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
<<<<<<< HEAD
                        <label for="name" class="form-label">{{ __('Category name') }}</label>
=======
                        <label for="name" class="form-label">Nama Kategori</label>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
<<<<<<< HEAD
                        <label for="description" class="form-label">{{ __('Description') }}</label>
=======
                        <label for="description" class="form-label">Deskripsi</label>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
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
                    <button type="submit" class="btn btn-primary">{{ __('Save category') }}</button>
=======
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
