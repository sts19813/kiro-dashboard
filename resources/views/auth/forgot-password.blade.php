@extends('layouts.auth')

@section('title', 'Restablecer contraseña')

@section('auth_content')
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">¿Olvidaste tu contraseña?</h1>
        <div class="text-gray-500 fw-semibold fs-6">
            Ingresa tu correo electrónico y te enviaremos un enlace para restablecerla.
        </div>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="form w-100" novalidate>
        @csrf

        <div class="fv-row mb-8">
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Correo electrónico"
                autocomplete="username"
                class="form-control bg-transparent @error('email') is-invalid @enderror"
                required
                autofocus />
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Enviar enlace de recuperación</span>
            </button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            ¿Recordaste tu contraseña?
            <a href="{{ route('login') }}" class="link-primary">Inicia sesión</a>
        </div>
    </form>
@endsection
