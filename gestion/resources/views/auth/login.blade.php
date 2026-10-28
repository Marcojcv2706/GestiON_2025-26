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

        /*
         * El x-guest-layout agrega su propio fondo.
         * Lo anulamos para que se vea exactamente el fondo del welcome.
         */
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

        .login-page {
            position: relative;
            z-index: 1;

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 2rem 1rem;
        }

        .login-card {
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
            margin-bottom: 1.2rem;
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

        .remember {
            display: flex;
            align-items: center;

            gap: 0.5rem;

            color: var(--gris-texto);

            font-size: 0.8rem;

            cursor: pointer;
        }

        .remember input {
            accent-color: var(--azul);
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 1rem;

            margin-top: 1.5rem;
        }

        .forgot-password {
            color: var(--gris-texto);

            font-size: 0.78rem;

            text-decoration: none;

            transition: color 0.2s ease;
        }

        .forgot-password:hover {
            color: var(--azul);
        }

        .login-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-width: 145px;

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

        .login-button:hover {
            background: var(--amarillo);
            border-color: var(--amarillo);
            color: var(--azul);

            transform: translateY(-2px);

            box-shadow:
                0 8px 18px rgba(0, 51, 102, 0.15);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .register-link {
            margin-top: 1.5rem;
            padding-top: 1.25rem;

            border-top: 1px solid rgba(0, 51, 102, 0.08);

            text-align: center;

            color: var(--gris-texto);

            font-size: 0.85rem;
        }

        .register-link a {
            color: var(--azul);

            font-weight: 600;

            text-decoration: none;
        }

        .register-link a:hover {
            color: var(--amarillo);
        }

        .footer {
            margin-top: 1.5rem;

            text-align: center;

            color: #94a3b8;

            font-size: 0.75rem;
        }

        .error-message {
            margin-top: 0.4rem;

            color: #dc2626;

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
            .login-page {
                padding: 1rem;
            }

            .login-card {
                padding: 1.75rem 1.5rem;
            }

            .form-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .login-button {
                width: 100%;
            }

            .forgot-password {
                text-align: center;
            }

            .background-photo {
                width: 85%;
                right: -25%;
                opacity: 0.18;
            }
        }
    </style>

    <div class="background-photo"></div>

    <div class="login-page">

        <div class="login-card">

            <div class="logo-wrapper">
                <img
                    src="{{ asset('images/GestiONsolologo.png') }}"
                    alt="GestiON"
                    class="logo"
                >
            </div>

            <h1 class="title">
                Iniciar Sesión
            </h1>

            <p class="subtitle">
                Ingresá a la plataforma de gestión del ISAM.
            </p>

            <div class="yellow-line"></div>

            <x-auth-session-status
                class="mb-4"
                :status="session('status')"
            />

            <form method="POST" action="{{ route('login') }}">
                @csrf

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
                        autofocus
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
                        placeholder="Ingresá tu contraseña"
                        required
                        autocomplete="current-password"
                    >

                    @if ($errors->has('password'))
                        <div class="error-message">
                            {{ $errors->first('password') }}
                        </div>
                    @endif
                </div>

                <label
                    for="remember_me"
                    class="remember"
                >
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                    >

                    <span>
                        Recordarme
                    </span>
                </label>

                <div class="form-actions">

                    @if (Route::has('password.request'))
                        <a
                            href="{{ route('password.request') }}"
                            class="forgot-password"
                        >
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif

                    <button
                        type="submit"
                        class="login-button"
                    >
                        Iniciar Sesión
                    </button>

                </div>
            </form>

            @if (Route::has('register'))
                <div class="register-link">
                    ¿No tenés una cuenta?
                    <a href="{{ route('register') }}">
                        Registrate
                    </a>
                </div>
            @endif

            <div class="footer">
                GestiON · Gestión de Eventos
            </div>

        </div>

    </div>

</x-guest-layout>