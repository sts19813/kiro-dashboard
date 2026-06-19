@extends('layouts.app')

@section('title', 'Mi perfil')

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap flex-stack gap-4 mb-8">
            <div>
                <h1 class="fw-bold text-gray-900 mb-1">Mi perfil</h1>
                <div class="text-muted">Administra tu información personal y seguridad.</div>
            </div>
            <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                <i class="ki-outline ki-pencil fs-2"></i>
                Editar perfil
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
                        <div class="d-flex justify-content-center gap-3">
                            @if ($user->google_id)
                                <span class="badge badge-light-info">Google conectado</span>
                            @endif
                            <span class="badge {{ $user->email_verified_at ? 'badge-light-success' : 'badge-light-warning' }}">
                                {{ $user->email_verified_at ? 'Correo verificado' : 'Correo pendiente' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card card-flush h-100">
                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="fw-bold mb-0">Detalles de la cuenta</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-6">
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="text-muted fs-7 mb-1">Nombre completo</div>
                                    <div class="fw-bold text-gray-900">{{ $user->name }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="text-muted fs-7 mb-1">Correo electrónico</div>
                                    <div class="fw-bold text-gray-900">{{ $user->email }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="text-muted fs-7 mb-1">Cuenta creada</div>
                                    <div class="fw-bold text-gray-900">{{ $user->created_at?->format('d/m/Y') ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border border-dashed rounded p-5 h-100">
                                    <div class="text-muted fs-7 mb-1">Última actualización</div>
                                    <div class="fw-bold text-gray-900">{{ $user->updated_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
