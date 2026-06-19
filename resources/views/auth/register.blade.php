@extends('layouts.auth')

@section('title', 'Crear cuenta')

@section('auth_content')
    <form class="form w-100" method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">Crear cuenta</h1>
            <div class="text-gray-500 fw-semibold fs-6">Regístrate para administrar tu dashboard</div>
        </div>

        <div class="d-grid mb-8">
            <a href="{{ route('google.redirect') }}" class="btn btn-flex btn-light btn-lg w-100">
                <img alt="Google" src="{{ asset('/metronic/assets/media/svg/brand-logos/google-icon.svg') }}"
                    class="h-20px me-3" />
                Continuar con Google
            </a>
        </div>

        <div class="separator separator-content my-10">
            <span class="w-125px text-gray-500 fw-semibold fs-7">O con correo</span>
        </div>

        <div class="fv-row mb-8">
            <input
                type="text"
                placeholder="Nombre completo"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                class="form-control bg-transparent @error('name') is-invalid @enderror" />
            @error('name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-8">
            <input
                type="email"
                placeholder="Correo electrónico"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                class="form-control bg-transparent @error('email') is-invalid @enderror" />
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-8">
            <input
                type="password"
                placeholder="Contraseña"
                name="password"
                required
                autocomplete="new-password"
                class="form-control bg-transparent @error('password') is-invalid @enderror" />
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-10">
            <input
                type="password"
                placeholder="Confirmar contraseña"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="form-control bg-transparent @error('password_confirmation') is-invalid @enderror" />
            @error('password_confirmation')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Crear cuenta</span>
            </button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="link-primary fw-semibold">Inicia sesión</a>
        </div>
    </form>
@endsection
