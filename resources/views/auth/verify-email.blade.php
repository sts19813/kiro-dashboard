@extends('layouts.auth')

@section('title', 'Verificar correo')

@section('auth_content')
    <div class="text-center mb-11">
        <h1 class="text-gray-900 fw-bolder mb-3">Verifica tu correo</h1>
        <div class="text-gray-500 fw-semibold fs-6">
            Te enviamos un enlace de verificación. Si no lo recibiste, podemos enviarte otro.
        </div>
    </div>

    <div class="d-grid gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-100">Reenviar correo de verificación</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-light w-100">Cerrar sesión</button>
        </form>
    </div>
@endsection
