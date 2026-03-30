@extends('layouts.app')

@section('title', 'Editar perfil')

@section('content')
    <div class="container-fluid">
        <div class="card card-flush">
            <div class="card-header align-items-center py-6 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">{{ __('Editar perfil') }}</h3>
                    <div class="text-muted fs-7">{{ __('Actualiza tus datos, tu foto de perfil y la seguridad de tu cuenta.') }}</div>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('profile.index') }}" class="btn btn-light">{{ __('Volver al perfil') }}</a>
                </div>
            </div>
            <div class="card-body py-6">
                <div class="row gx-10 gy-10">
                    <div class="col-12 col-xl-4">
                        <div class="card card-flush h-100">
                            <div class="card-body text-center py-10">
                                <div class="symbol symbol-120px symbol-circle mx-auto mb-6">
                                    @if ($user->profile_image)
                                        <img src="{{ asset('storage/' . $user->profile_image) }}" alt="{{ $user->name }}" class="symbol-label" style="object-fit: cover;">
                                    @else
                                        <div class="symbol-label fw-bold d-flex justify-content-center align-items-center bg-primary text-white fs-2">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <h3 class="fw-bold mb-1">{{ $user->name }}</h3>
                                <div class="text-muted mb-5">{{ $user->email }}</div>
                                <div class="d-grid gap-2">
                                    <a href="{{ route('profile.index') }}" class="btn btn-light btn-sm">{{ __('Ver perfil') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-8">
                        <div class="row gy-10">
                            <div class="col-12">
                                <div class="card card-flush h-100">
                                    <div class="card-header py-5">
                                        <h3 class="fw-bold">{{ __('Información de perfil') }}</h3>
                                    </div>
                                    <div class="card-body py-5">
                                        @include('profile.partials.update-profile-information-form')
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card card-flush h-100">
                                    <div class="card-header py-5">
                                        <h3 class="fw-bold">{{ __('Seguridad') }}</h3>
                                    </div>
                                    <div class="card-body py-5">
                                        @include('profile.partials.update-password-form')
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6">
                                <div class="card card-flush h-100">
                                    <div class="card-header py-5">
                                        <h3 class="fw-bold text-danger">{{ __('Eliminar cuenta') }}</h3>
                                    </div>
                                    <div class="card-body py-5">
                                        @include('profile.partials.delete-user-form')
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
