@extends('layouts.auth')

@section('title', 'Establecer nueva contraseña')

@section('auth_content')
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Establecer nueva contraseña</h1>
        <div class="text-gray-500 fw-semibold fs-6">
            Ingresa tu nueva contraseña para continuar.
        </div>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="form w-100" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="fv-row mb-8">
            <input
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                placeholder="Correo electrónico"
                autocomplete="username"
                class="form-control bg-transparent @error('email') is-invalid @enderror"
                required
                autofocus />
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-8">
            <input
                type="password"
                name="password"
                placeholder="Nueva contraseña"
                autocomplete="new-password"
                class="form-control bg-transparent @error('password') is-invalid @enderror"
                required />
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-10">
            <input
                type="password"
                name="password_confirmation"
                placeholder="Confirmar nueva contraseña"
                autocomplete="new-password"
                class="form-control bg-transparent @error('password_confirmation') is-invalid @enderror"
                required />
            @error('password_confirmation')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Guardar nueva contraseña</span>
            </button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            ¿Ya tienes una cuenta?
            <a href="{{ route('login') }}" class="link-primary">Inicia sesión</a>
        </div>
    </form>
@endsection
