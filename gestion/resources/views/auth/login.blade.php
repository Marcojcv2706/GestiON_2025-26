<x-guest-layout>

    <style>
        :root {
            --azul: #003366;
            --amarillo: #EDC001;
            --blanco: #FFFFFF;
            --gris: #f5f7fa;
            --gris-texto: #64748b;
        }

        body {
            min-height: 100vh;
            font-family: 'Instrument Sans', sans-serif;
            background:
                radial-gradient(
                    circle at 50% 40%,
                    rgba(237, 192, 1, 0.08),
                    transparent 35%
                ),
                var(--gris);
            color: var(--azul);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .login-background {
            position: fixed;
            top: -20%;
            right: -12%;
            width: 60%;
            height: 140%;
            background-image:
                linear-gradient(
                    90deg,
                    var(--gris) 0%,
                    rgba(245, 247, 250, 0.75) 25%,
                    rgba(245, 247, 250, 0.15) 70%,
                    rgba(245, 247, 250, 0) 100%
                ),
                url('/images/isam_blue.png');
            background-size: cover;
            background-position: center;
            transform: rotate(-12deg);
            filter: blur(5px);
            opacity: 0.28;
            z-index: -1;
            border-radius: 50px;
            pointer-events: none;
        }

        .login-container {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    overflow: hidden;
}
        

       .login-card {
    width: 100%;
    max-width: 430px;
    background: var(--blanco);
    border: 1px solid rgba(0, 51, 102, 0.10);
    border-radius: 16px;
    padding: 2rem;
    box-shadow:
        0 20px 50px rgba(0, 51, 102, 0.10);
    animation: aparecer 600ms ease-out;
}
        html,
body {
    height: 100%;
    overflow: hidden;
}

        .logo {
            display: block;
            width: 230px;
            max-width: 80%;
            height: auto;
            margin: 0 auto 1.5rem;
        }

        .title {
            text-align: center;
            color: var(--azul);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
        }

        .subtitle {
            text-align: center;
            color: var(--gris-texto);
            font-size: 0.9rem;
            margin-bottom: 1.75rem;
        }

        .institutional-line {
            width: 55px;
            height: 4px;
            margin: 0 auto 1.75rem;
            background-color: var(--amarillo);
            border-radius: 20px;
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
            border: 1px solid rgba(0, 51, 102, 0.18);
            border-radius: 8px;
            background: var(--blanco);
            color: var(--azul);
            outline: none;
            transition:
                border-color 180ms ease,
                box-shadow 180ms ease;
        }

        .form-input:focus {
            border-color: var(--azul);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.08);
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .remember input {
            accent-color: var(--azul);
        }

        .remember span {
            color: var(--gris-texto);
            font-size: 0.85rem;
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
            font-size: 0.8rem;
            text-decoration: none;
        }

        .forgot-password:hover {
            color: var(--azul);
        }

        .login-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.7rem 1.4rem;
            border: 1px solid var(--azul);
            border-radius: 8px;
            background-color: var(--azul);
            color: var(--blanco);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition:
                transform 180ms ease,
                background-color 180ms ease,
                box-shadow 180ms ease;
        }

        .login-button:hover {
            background-color: var(--amarillo);
            border-color: var(--amarillo);
            color: var(--azul);
            transform: translateY(-2px);
            box-shadow:
                0 8px 18px rgba(0, 51, 102, 0.18);
        }

        .register-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(0, 51, 102, 0.08);
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
            text-align: center;
            margin-top: 1.5rem;
            color: #94a3b8;
            font-size: 0.75rem;
        }

        @keyframes aparecer {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @media (max-width: 600px) {
            .login-card {
                padding: 2rem 1.5rem;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .login-button {
                width: 100%;
            }

            .forgot-password {
                text-align: center;
            }

            .login-background {
                width: 85%;
                right: -25%;
                opacity: 0.18;
            }
        }
    </style>

    <div class="login-background"></div>

    <div class="login-container">

        <div class="login-card">

            <img
                src="{{ asset('images/GestiONsolologo.png') }}"
                alt="Logo de GestiON"
                class="logo"
            >

            <h1 class="title">Iniciar Sesión</h1>

            <p class="subtitle">
                Ingresá a la plataforma de gestión del ISAM.
            </p>

            <div class="institutional-line"></div>

            <x-auth-session-status
                class="mb-4"
                :status="session('status')"
            />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div>
                    <label for="email" class="form-label">
                        Correo electrónico
                    </label>

                    <input
                        id="email"
                        class="form-input"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                    >

                    <x-input-error
                        :messages="$errors->get('email')"
                        class="mt-2"
                    />
                </div>

                <div class="mt-4">
                    <label for="password" class="form-label">
                        Contraseña
                    </label>

                    <input
                        id="password"
                        class="form-input"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >

                    <x-input-error
                        :messages="$errors->get('password')"
                        class="mt-2"
                    />
                </div>

                <label for="remember_me" class="remember">
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
                            class="forgot-password"
                            href="{{ route('password.request') }}"
                        >
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif

                    <button type="submit" class="login-button">
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
                GestiON
            </div>

        </div>

    </div>

</x-guest-layout>