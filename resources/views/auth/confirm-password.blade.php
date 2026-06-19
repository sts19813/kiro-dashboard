@extends('layouts.auth')

@section('title', 'Confirmar contraseña')

@section('auth_content')
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Confirmar contraseña</h1>
        <div class="text-gray-500 fw-semibold fs-6">
            Esta es un área segura. Confirma tu contraseña para continuar.
        </div>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="form w-100" novalidate>
        @csrf

        <div class="fv-row mb-8">
            <input
                type="password"
                name="password"
                placeholder="Contraseña"
                autocomplete="current-password"
                class="form-control bg-transparent @error('password') is-invalid @enderror"
                required
                autofocus />
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Confirmar</span>
            </button>
        </div>
    </form>
@endsection
