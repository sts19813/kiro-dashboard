@extends('layouts.auth')

@section('title', 'Iniciar Sesion')

@section('auth_content')
    <form class="form w-100" method="POST" action="{{ route('login') }}">
        @csrf

        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">Sign In</h1>
            <div class="text-gray-500 fw-semibold fs-6">Accede con tu cuenta</div>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-8">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-8">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="fv-row mb-8">
            <input
                type="email"
                placeholder="Email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="form-control bg-transparent @error('email') is-invalid @enderror" />
            @error('email')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-3">
            <input
                type="password"
                placeholder="Password"
                name="password"
                required
                autocomplete="current-password"
                class="form-control bg-transparent @error('password') is-invalid @enderror" />
            @error('password')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
            <label class="form-check form-check-custom form-check-solid">
                <input class="form-check-input me-2" type="checkbox" name="remember" value="1" />
                <span class="form-check-label">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-primary">Forgot Password ?</a>
            @endif
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Sign In</span>
            </button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            Not a Member yet?
            <a href="{{ route('register') }}" class="link-primary">Sign up</a>
        </div>
    </form>
@endsection
