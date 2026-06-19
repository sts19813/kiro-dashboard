@extends('layouts.app')

@section('title', 'Editar perfil')

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap flex-stack gap-4 mb-8">
            <div>
                <h1 class="fw-bold text-gray-900 mb-1">Editar perfil</h1>
                <div class="text-muted">Actualiza tu foto, nombre, correo y contraseña.</div>
            </div>
            <a href="{{ route('profile.index') }}" class="btn btn-light">
                <i class="ki-outline ki-arrow-left fs-2"></i>
                Volver
            </a>
        </div>

        <div class="row g-8">
            <div class="col-12 col-xl-4">
                <div class="card card-flush h-100">
                    <div class="card-body text-center py-10">
                        <div class="symbol symbol-125px symbol-circle mx-auto mb-6">
                            @if ($user->profile_image || $user->google_avatar_url)
                                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" style="object-fit: cover;">
                            @else
                                <div class="symbol-label bg-light-primary text-primary fw-bold fs-1">{{ $user->initials() }}</div>
                            @endif
                        </div>
                        <h2 class="fw-bold mb-1">{{ $user->name }}</h2>
                        <div class="text-muted mb-6">{{ $user->email }}</div>
                        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 text-start">
                            <i class="ki-outline ki-information-5 fs-2tx text-primary me-4"></i>
                            <div class="text-gray-700 fw-semibold fs-7">
                                La foto de perfil se puede cambiar desde el formulario de información.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="row g-8">
                    <div class="col-12">
                        <div class="card card-flush">
                            <div class="card-header">
                                <div class="card-title">
                                    <h3 class="fw-bold mb-0">Información personal</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                @include('profile.partials.update-profile-information-form')
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card card-flush">
                            <div class="card-header">
                                <div class="card-title">
                                    <h3 class="fw-bold mb-0">Cambiar contraseña</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                @include('profile.partials.update-password-form')
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card card-flush border border-danger border-dashed">
                            <div class="card-header">
                                <div class="card-title">
                                    <h3 class="fw-bold text-danger mb-0">Eliminar cuenta</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                @include('profile.partials.delete-user-form')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
