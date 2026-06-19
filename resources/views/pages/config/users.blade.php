@extends('layouts.app')

@section('title', 'Administración de usuarios')

@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-wrap flex-stack gap-4 mb-8">
            <div>
                <h1 class="fw-bold text-gray-900 mb-1">Administración de usuarios</h1>
                <div class="text-muted">Consulta usuarios registrados y sus datos principales.</div>
            </div>
            <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                <i class="ki-outline ki-user-edit fs-2"></i>
                Editar mi perfil
            </a>
        </div>

        <div class="card card-flush">
            <div class="card-header align-items-center py-5 gap-3">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input
                            type="text"
                            data-users-search
                            class="form-control form-control-solid w-250px ps-12"
                            placeholder="Buscar usuario">
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="users_table">
                        <thead>
                            <tr class="text-start text-gray-700 fw-bold fs-7 text-uppercase gs-0">
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Acceso</th>
                                <th>Registro</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                            @forelse ($users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-circle symbol-45px me-5">
                                                @if ($user->profile_image || $user->google_avatar_url)
                                                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" style="object-fit: cover;">
                                                @else
                                                    <div class="symbol-label bg-light-primary text-primary fw-bold">{{ $user->initials() }}</div>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="text-gray-900 fw-bold">{{ $user->name }}</div>
                                                <div class="text-muted fs-7">Actualizado {{ $user->updated_at?->format('d/m/Y') ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if ($user->google_id)
                                            <span class="badge badge-light-info">Google</span>
                                        @else
                                            <span class="badge badge-light">Correo</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="text-end">
                                        @if ($user->is(auth()->user()))
                                            <a href="{{ route('profile.edit') }}" class="btn btn-icon btn-light btn-active-light-primary btn-sm">
                                                <i class="ki-outline ki-pencil fs-2"></i>
                                            </a>
                                        @else
                                            <span class="text-muted fs-7">Solo lectura</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-10">No hay usuarios registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end pt-6">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var table = document.getElementById('users_table');
            var search = document.querySelector('[data-users-search]');

            if (!table || !search) {
                return;
            }

            search.addEventListener('input', function () {
                var needle = search.value.toLowerCase();

                table.querySelectorAll('tbody tr').forEach(function (row) {
                    row.style.display = row.textContent.toLowerCase().includes(needle) ? '' : 'none';
                });
            });
        });
    </script>
@endpush
