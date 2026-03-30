<section>
    <div class="mb-8">
        <h3 class="fw-bold mb-2">{{ __('Información de perfil') }}</h3>
        <div class="text-muted fs-7">{{ __("Actualiza el nombre, correo y foto de perfil de tu cuenta.") }}</div>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="form">
        @csrf
        @method('patch')

        <div class="mb-6">
            <label class="form-label">{{ __('Nombre completo') }}</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" class="form-control form-control-solid @error('name') is-invalid @enderror" />
            @error('name')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-6">
            <label class="form-label">{{ __('Correo electrónico') }}</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="form-control form-control-solid @error('email') is-invalid @enderror" />
            @error('email')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3">
                    <p class="text-muted fs-7">
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="btn btn-link p-0 align-baseline">{{ __('Click here to re-send the verification email.') }}</button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <div class="alert alert-success d-flex align-items-center py-3">{{ __('A new verification link has been sent to your email address.') }}</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="mb-6">
            <label class="form-label">{{ __('Foto de perfil') }}</label>
            <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" class="form-control form-control-solid @error('profile_image') is-invalid @enderror" />
            <div class="form-text">{{ __('Formatos permitidos: JPG, PNG, WEBP. Máximo 2MB.') }}</div>
            @error('profile_image')
                <div class="text-danger fs-7 mt-2">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex align-items-center gap-4">
            <button type="submit" class="btn btn-primary">{{ __('Guardar cambios') }}</button>

            @if (session('status') === 'profile-updated')
                <span class="text-success fs-7">{{ __('Guardado.') }}</span>
            @endif
        </div>
    </form>
</section>
