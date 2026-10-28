<x-guest-layout>
    <h1 class="guest-form-title">Verifica tu correo</h1>
    <p class="guest-form-copy">Te enviamos un enlace por correo. Ábrelo para confirmar tu dirección y activar tu cuenta.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="guest-status-message" role="status">
            Enviamos un nuevo enlace de verificación a tu correo.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Reenviar enlace
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="guest-logout-button">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
