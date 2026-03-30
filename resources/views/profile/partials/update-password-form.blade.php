<section>
    <div class="mb-8">
        <h3 class="fw-bold mb-2">{{ __('Actualizar contraseña') }}</h3>
        <div class="text-muted fs-7">{{ __('Asegura tu cuenta con una contraseña nueva y robusta.') }}</div>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="form">
        @csrf
        @method('put')

        <div class="mb-6">
            <label class="form-label">{{ __('Contraseña actual') }}</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="form-control form-control-solid @error('current_password') is-invalid @enderror" />
            @error('current_password')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-6">
            <label class="form-label">{{ __('Nueva contraseña') }}</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password" class="form-control form-control-solid @error('password') is-invalid @enderror" />
            @error('password')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-6">
            <label class="form-label">{{ __('Confirmar contraseña') }}</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-control form-control-solid" />
        </div>

        <div class="d-flex align-items-center gap-4">
            <button type="submit" class="btn btn-primary">{{ __('Guardar contraseña') }}</button>

            @if (session('status') === 'password-updated')
                <span class="text-success fs-7">{{ __('Guardado.') }}</span>
            @endif
        </div>
    </form>
</section>
