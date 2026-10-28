<x-guest-layout>
    <h1 class="guest-form-title">Confirma tu contraseña</h1>
    <p class="guest-form-copy">Por seguridad, vuelve a escribir tu contraseña para continuar.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="guest-form-group">
            <x-input-label for="password" value="Contraseña" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                Confirmar
            </x-primary-button>
        </div>
    </form>

    <a class="guest-back-link" href="{{ route('login') }}">Volver al inicio</a>
</x-guest-layout>
