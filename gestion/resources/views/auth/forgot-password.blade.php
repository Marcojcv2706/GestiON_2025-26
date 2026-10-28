<x-guest-layout>
    <h1 class="guest-form-title">Recuperar contraseña</h1>
    <p class="guest-form-copy">Escribe tu correo y te enviaremos un enlace para crear una contraseña nueva.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="guest-form-group">
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Enviar enlace
            </x-primary-button>
        </div>
    </form>

    <a class="guest-back-link" href="{{ route('login') }}">Volver a iniciar sesión</a>
</x-guest-layout>
