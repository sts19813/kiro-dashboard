@extends('layouts.auth')

@section('title', 'Iniciar sesión')

@section('auth_content')
    <form class="form w-100" method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">Iniciar sesión</h1>
            <div class="text-gray-500 fw-semibold fs-6">Accede a tu dashboard</div>
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
                type="email"
                placeholder="Correo electrónico"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="form-control bg-transparent @error('email') is-invalid @enderror" />
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-3">
            <input
                type="password"
                placeholder="Contraseña"
                name="password"
                required
                autocomplete="current-password"
                class="form-control bg-transparent @error('password') is-invalid @enderror" />
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
            <label class="form-check form-check-custom form-check-solid">
                <input class="form-check-input me-2" type="checkbox" name="remember" value="1" />
                <span class="form-check-label">Recuérdame</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-primary">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Iniciar sesión</span>
            </button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            ¿No tienes una cuenta?
            <a href="{{ route('register') }}" class="link-primary">Regístrate</a>
        </div>
    </form>
@endsection
