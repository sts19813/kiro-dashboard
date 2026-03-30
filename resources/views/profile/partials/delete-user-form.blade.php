<section>
    <div class="mb-8">
        <h3 class="fw-bold text-danger mb-2">{{ __('Eliminar cuenta') }}</h3>
        <div class="text-muted fs-7">{{ __('Esta acción eliminará permanentemente tu cuenta y datos asociados.') }}</div>
    </div>

    <button type="button" class="btn btn-danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        {{ __('Eliminar cuenta') }}
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="form p-6">
            @csrf
            @method('delete')

            <h3 class="fw-bold mb-3">{{ __('¿Seguro que quieres eliminar tu cuenta?') }}</h3>
            <p class="text-muted mb-6">{{ __('Una vez eliminada, tu cuenta y todos los datos asociados no podrán recuperarse.') }}</p>

            <div class="mb-6">
                <label class="form-label">{{ __('Contraseña') }}</label>
                <input id="password" name="password" type="password" class="form-control form-control-solid @error('password') is-invalid @enderror" placeholder="{{ __('Contraseña') }}" />
                @error('password')
                    <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end gap-3">
                <button type="button" class="btn btn-light" x-on:click="$dispatch('close')">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('Eliminar cuenta') }}</button>
            </div>
        </form>
    </x-modal>
</section>
