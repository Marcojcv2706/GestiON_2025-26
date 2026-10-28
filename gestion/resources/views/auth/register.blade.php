<x-guest-layout>

    <style>
        :root {
            --azul: #003366;
            --amarillo: #EDC001;
            --blanco: #FFFFFF;
            --gris: #f5f7fa;
            --gris-texto: #64748b;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
            background: #f5f7fa !important;
        }

        body {
            min-height: 100vh;
            font-family: 'Instrument Sans', sans-serif;
            color: var(--azul);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body > div,
        body > div > div,
        .min-h-screen,
        .bg-gray-100,
        .bg-gray-50 {
            background: transparent !important;
        }

        .background-photo {
            position: fixed;
            top: -20%;
            right: -12%;
            width: 60%;
            height: 140%;

            background-image:
                linear-gradient(
                    90deg,
                    #f5f7fa 0%,
                    rgba(245, 247, 250, 0.75) 25%,
                    rgba(245, 247, 250, 0.15) 70%,
                    rgba(245, 247, 250, 0) 100%
                ),
                url('/images/isam_blue.png');

            background-size: cover;
            background-position: center;

            transform: rotate(-12deg);

            opacity: 0.28;

            z-index: 0;
            border-radius: 50px;
            pointer-events: none;
        }

        .register-page {
            position: relative;
            z-index: 1;

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 2rem 1rem;
        }

        .register-card {
            width: 100%;
            max-width: 430px;

            background: #ffffff;

            border: 1px solid rgba(0, 51, 102, 0.08);
            border-radius: 12px;

            padding: 2rem;

            box-shadow:
                0 20px 50px rgba(0, 51, 102, 0.10),
                0 4px 12px rgba(0, 51, 102, 0.05);

            animation: aparecer 0.6s ease-out;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 1.5rem;
        }

        .logo {
            display: block;

            width: 230px;
            max-width: 80%;
            height: auto;
        }

        .title {
            margin: 0;

            text-align: center;

            color: var(--azul);

            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .subtitle {
            margin: 0.5rem 0 0;

            text-align: center;

            color: var(--gris-texto);

            font-size: 0.9rem;
            line-height: 1.5;
        }

        .yellow-line {
            width: 55px;
            height: 4px;

            margin: 1.5rem auto 1.75rem;

            background: var(--amarillo);

            border-radius: 20px;
        }

        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-label {
            display: block;

            margin-bottom: 0.4rem;

            color: var(--azul);

            font-size: 0.875rem;
            font-weight: 600;
        }

        .form-input {
            width: 100%;

            padding: 0.7rem 0.85rem;

            border: 1px solid #d8dee8;
            border-radius: 8px;

            background: #ffffff;
            color: var(--azul);

            font-family: inherit;
            font-size: 0.875rem;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .form-input:focus {
            border-color: var(--azul);

            box-shadow:
                0 0 0 3px rgba(0, 51, 102, 0.08);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .error-message {
            margin-top: 0.4rem;

            color: #dc2626;

            font-size: 0.75rem;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;

            margin-top: 1.5rem;
        }

        .register-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 100%;

            padding: 0.7rem 1.4rem;

            border: 1px solid var(--azul);
            border-radius: 8px;

            background: var(--azul);
            color: var(--blanco);

            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;

            cursor: pointer;

            transition:
                background-color 0.2s ease,
                color 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .register-button:hover {
            background: var(--amarillo);
            border-color: var(--amarillo);
            color: var(--azul);

            transform: translateY(-2px);

            box-shadow:
                0 8px 18px rgba(0, 51, 102, 0.15);
        }

        .register-button:active {
            transform: translateY(0);
        }

        .login-link {
            margin-top: 1.5rem;
            padding-top: 1.25rem;

            border-top: 1px solid rgba(0, 51, 102, 0.08);

            text-align: center;

            color: var(--gris-texto);

            font-size: 0.85rem;
        }

        .login-link a {
            color: var(--azul);

            font-weight: 600;

            text-decoration: none;
        }

        .login-link a:hover {
            color: var(--amarillo);
        }

        .footer {
            margin-top: 1.5rem;

            text-align: center;

            color: #94a3b8;

            font-size: 0.75rem;
        }

        @keyframes aparecer {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 600px) {
            .register-page {
                padding: 1rem;
            }

            .register-card {
                padding: 1.75rem 1.5rem;
            }

            .background-photo {
                width: 85%;
                right: -25%;
                opacity: 0.18;
            }
        }
    </style>

    <div class="background-photo"></div>

    <div class="register-page">

        <div class="register-card">

            <div class="logo-wrapper">
                <img
                    src="{{ asset('images/GestiONsolologo.png') }}"
                    alt="GestiON"
                    class="logo"
                >
            </div>

            <h1 class="title">
                Crear Cuenta
            </h1>

            <p class="subtitle">
                Registrate para acceder a GestiON.
            </p>

            <div class="yellow-line"></div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="form-group">
                    <label
                        for="name"
                        class="form-label"
                    >
                        Nombre
                    </label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-input"
                        placeholder="Ingresá tu nombre"
                        required
                        autofocus
                        autocomplete="name"
                    >

                    @if ($errors->has('name'))
                        <div class="error-message">
                            {{ $errors->first('name') }}
                        </div>
                    @endif
                </div>

                <div class="form-group">
                    <label
                        for="email"
                        class="form-label"
                    >
                        Correo electrónico
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-input"
                        placeholder="Ingresá tu correo electrónico"
                        required
                        autocomplete="username"
                    >

                    @if ($errors->has('email'))
                        <div class="error-message">
                            {{ $errors->first('email') }}
                        </div>
                    @endif
                </div>

                <div class="form-group">
                    <label
                        for="password"
                        class="form-label"
                    >
                        Contraseña
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-input"
                        placeholder="Ingresá una contraseña"
                        required
                        autocomplete="new-password"
                    >

                    @if ($errors->has('password'))
                        <div class="error-message">
                            {{ $errors->first('password') }}
                        </div>
                    @endif
                </div>

                <div class="form-group">
                    <label
                        for="password_confirmation"
                        class="form-label"
                    >
                        Confirmar contraseña
                    </label>

                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="form-input"
                        placeholder="Repetí tu contraseña"
                        required
                        autocomplete="new-password"
                    >

                    @if ($errors->has('password_confirmation'))
                        <div class="error-message">
                            {{ $errors->first('password_confirmation') }}
                        </div>
                    @endif
                </div>

                <div class="form-actions">
                    <button
                        type="submit"
                        class="register-button"
                    >
                        Registrarse
                    </button>
                </div>
            </form>

            <div class="login-link">
                ¿Ya tenés una cuenta?
                <a href="{{ route('login') }}">
                    Iniciá sesión
                </a>
            </div>

            <div class="footer">
                GestiON · Gestión de Eventos
            </div>

        </div>

    </div>

</x-guest-layout>