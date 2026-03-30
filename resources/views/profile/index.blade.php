@extends('layouts.app')

@section('title', 'Perfil')

@section('content')
    <div class="container-fluid">
        <div class="card card-flush">
            <div class="card-header align-items-center py-6 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">{{ __('Perfil') }}</h3>
                    <div class="text-muted fs-7">{{ __('Revisa tu información y administra tu cuenta desde aquí.') }}</div>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">{{ __('Editar perfil') }}</a>
                </div>
            </div>
            <div class="card-body py-6">
                <div class="row gy-6">
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
                                <div class="row gx-4">
                                    <div class="col-6">
                                        <span class="text-muted fs-7">{{ __('Cuenta creada') }}</span>
                                        <div class="fw-semibold">{{ $user->created_at?->format('d/m/Y') ?? '-' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted fs-7">{{ __('Último acceso') }}</span>
                                        <div class="fw-semibold">{{ $user->updated_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-8">
                        <div class="card card-flush h-100">
                            <div class="card-header py-5">
                                <h3 class="fw-bold">{{ __('Detalles de la cuenta') }}</h3>
                            </div>
                            <div class="card-body py-8">
                                <div class="row g-5">
                                    <div class="col-md-6">
                                        <div class="text-muted fs-7">{{ __('Estado de correo') }}</div>
                                        <div class="fw-semibold">{{ $user->email_verified_at ? __('Verificado') : __('Pendiente') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted fs-7">{{ __('Correo electrónico') }}</div>
                                        <div class="fw-semibold">{{ $user->email }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted fs-7">{{ __('Nombre completo') }}</div>
                                        <div class="fw-semibold">{{ $user->name }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted fs-7">{{ __('Perfil actualizado') }}</div>
                                        <div class="fw-semibold">{{ $user->created_at?->format('d/m/Y') ?? '-' }}</div>
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
