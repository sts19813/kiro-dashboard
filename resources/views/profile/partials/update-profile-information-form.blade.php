<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="form">
        @csrf
        @method('patch')

        <div class="row g-6">
            <div class="col-md-6">
                <label class="required form-label">Nombre completo</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $user->name) }}"
                    required
                    autofocus
                    autocomplete="name"
                    class="form-control form-control-solid @error('name') is-invalid @enderror" />
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="required form-label">Correo electrónico</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $user->email) }}"
                    required
                    autocomplete="username"
                    class="form-control form-control-solid @error('email') is-invalid @enderror" />
                @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label class="form-label">Foto de perfil</label>
                <input
                    type="file"
                    name="profile_image"
                    accept="image/jpeg,image/png,image/webp"
                    class="form-control form-control-solid @error('profile_image') is-invalid @enderror" />
                <div class="form-text">Formatos permitidos: JPG, PNG o WEBP. Máximo 2 MB.</div>
                @error('profile_image')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-5 mt-8">
                <i class="ki-outline ki-information-5 fs-2tx text-warning me-4"></i>
                <div class="fw-semibold text-gray-700">
                    Tu correo aún no está verificado.
                    <button form="send-verification" class="btn btn-link p-0 align-baseline">Reenviar verificación</button>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-end mt-8">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</section>
