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
            height: 100%;
            overflow: hidden;
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

        .register-container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow: hidden;
        }

        .register-card {
            width: 100%;
            max-width: 430px;
            background: var(--blanco);
            border: 1px solid rgba(0, 51, 102, 0.10);
            border-radius: 16px;
            padding: 1.8rem 2rem;
            box-shadow:
                0 20px 50px rgba(0, 51, 102, 0.10);
            animation: aparecer 600ms ease-out;
        }

        .logo {
            display: block;
            width: 210px;
            max-width: 80%;
            height: auto;
            margin: 0 auto 1rem;
        }

        .title {
            text-align: center;
            color: var(--azul);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }

        .subtitle {
            text-align: center;
            color: var(--gris-texto);
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
        }

        .institutional-line {
            width: 55px;
            height: 4px;
            margin: 0 auto 1.3rem;
            background-color: var(--amarillo);
            border-radius: 20px;
        }

        .form-group {
            margin-bottom: 0.9rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.35rem;
            color: var(--azul);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            padding: 0.65rem 0.85rem;
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
            box-shadow:
                0 0 0 3px rgba(0, 51, 102, 0.08);
        }

        .form-error {
            margin-top: 0.3rem;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1.2rem;
        }

        .login-link {
            color: var(--gris-texto);
            font-size: 0.8rem;
            text-decoration: none;
        }

        .login-link:hover {
            color: var(--azul);
        }

        .register-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.65rem 1.3rem;
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

        .register-button:hover {
            background-color: var(--amarillo);
            border-color: var(--amarillo);
            color: var(--azul);
            transform: translateY(-2px);
            box-shadow:
                0 8px 18px rgba(0, 51, 102, 0.18);
        }

        .footer {
            text-align: center;
            margin-top: 1.2rem;
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

            html,
            body {
                overflow: auto;
            }

            .register-container {
                min-height: 100vh;
                height: auto;
                padding: 1rem;
            }

            .register-card {
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .register-button {
                width: 100%;
            }

            .login-link {
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

    <div class="register-container">

        <div class="register-card">

            <img
                src="{{ asset('images/GestiONsolologo.png') }}"
                alt="Logo de GestiON"
                class="logo"
            >

            <h1 class="title">
                Crear cuenta
            </h1>

            <p class="subtitle">
                Registrate para acceder a la plataforma de gestión del ISAM.
            </p>

            <div class="institutional-line"></div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div class="form-group">

                    <label for="name" class="form-label">
                        Nombre
                    </label>

                    <input
                        id="name"
                        class="form-input"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                    >

                    <div class="form-error">
                        <x-input-error
                            :messages="$errors->get('name')"
                        />
                    </div>

                </div>

                <!-- Email -->
                <div class="form-group">

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
                        autocomplete="username"
                    >

                    <div class="form-error">
                        <x-input-error
                            :messages="$errors->get('email')"
                        />
                    </div>

                </div>

                <!-- Password -->
                <div class="form-group">

                    <label for="password" class="form-label">
                        Contraseña
                    </label>

                    <input
                        id="password"
                        class="form-input"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                    >

                    <div class="form-error">
                        <x-input-error
                            :messages="$errors->get('password')"
                        />
                    </div>

                </div>

                <!-- Confirm Password -->
                <div class="form-group">

                    <label for="password_confirmation" class="form-label">
                        Confirmar contraseña
                    </label>

                    <input
                        id="password_confirmation"
                        class="form-input"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    >

                    <div class="form-error">
                        <x-input-error
                            :messages="$errors->get('password_confirmation')"
                        />
                    </div>

                </div>

                <div class="form-actions">

                    <a
                        href="{{ route('login') }}"
                        class="login-link"
                    >
                        ¿Ya tenés una cuenta?
                        Iniciar Sesión
                    </a>

                    <button
                        type="submit"
                        class="register-button"
                    >
                        Registrarse
                    </button>

                </div>

            </form>

            <div class="footer">
                GestiON
            </div>

        </div>

    </div>

</x-guest-layout>