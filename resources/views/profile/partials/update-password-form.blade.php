<section>
    <form method="post" action="{{ route('password.update') }}" class="form">
        @csrf
        @method('put')

        <div class="row g-6">
            <div class="col-md-4">
                <label class="required form-label">Contraseña actual</label>
                <input
                    id="update_password_current_password"
                    name="current_password"
                    type="password"
                    autocomplete="current-password"
                    class="form-control form-control-solid @error('current_password', 'updatePassword') is-invalid @enderror" />
                @error('current_password', 'updatePassword')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="required form-label">Nueva contraseña</label>
                <input
                    id="update_password_password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    class="form-control form-control-solid @error('password', 'updatePassword') is-invalid @enderror" />
                @error('password', 'updatePassword')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="required form-label">Confirmar contraseña</label>
                <input
                    id="update_password_password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="form-control form-control-solid" />
            </div>
        </div>

        <div class="d-flex justify-content-end mt-8">
            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
        </div>
    </form>
</section>
