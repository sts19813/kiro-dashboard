@extends('layouts.auth')

@section('title', 'Crear Cuenta')

@section('auth_content')
    <form class="form w-100" method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
        @csrf

        <div class="text-center mb-11">
            <h1 class="text-gray-900 fw-bolder mb-3">Sign Up</h1>
            <div class="text-gray-500 fw-semibold fs-6">Crea tu cuenta para continuar</div>
        </div>

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
                type="text"
                placeholder="Name"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                class="form-control bg-transparent @error('name') is-invalid @enderror" />
            @error('name')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-8">
            <input
                type="email"
                placeholder="Email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                class="form-control bg-transparent @error('email') is-invalid @enderror" />
            @error('email')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-8">
            <label class="form-label fw-semibold text-gray-700">Profile image (optional)</label>
            <input
                type="file"
                name="profile_image"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                class="form-control bg-transparent @error('profile_image') is-invalid @enderror" />
            <div class="text-muted fs-7 mt-1">Formatos permitidos: JPG, PNG, WEBP. Maximo 2MB.</div>
            @error('profile_image')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-8">
            <input
                type="password"
                placeholder="Password"
                name="password"
                required
                autocomplete="new-password"
                class="form-control bg-transparent @error('password') is-invalid @enderror" />
            @error('password')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="fv-row mb-8">
            <input
                type="password"
                placeholder="Confirm Password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="form-control bg-transparent @error('password_confirmation') is-invalid @enderror" />
            @error('password_confirmation')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid mb-10">
            <button type="submit" class="btn btn-primary">
                <span class="indicator-label">Sign up</span>
            </button>
        </div>

        <div class="text-gray-500 text-center fw-semibold fs-6">
            Already have an Account?
            <a href="{{ route('login') }}" class="link-primary fw-semibold">Sign in</a>
        </div>
    </form>
@endsection
