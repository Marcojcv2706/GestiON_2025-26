<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bienvenido a GestiON</title>

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link
        href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap"
        rel="stylesheet"
    >

    <style>

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --azul: #003366;
            --amarillo: #EDC001;
            --amarillo-texto: #806600;
            --blanco: #FFFFFF;
            --gris: #f5f7fa;
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
            -moz-osx-font-smoothing: grayscale;

            overflow-x: hidden;
            position: relative;
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

            z-index: -2;

            border-radius: 50px;

            pointer-events: none;
        }

        .main-container {
            min-height: 100vh;

            display: flex;
            flex-direction: column;

            padding: 1.5rem 2.5rem;

            position: relative;

            z-index: 1;
        }

        .header-nav {
            display: flex;

            justify-content: flex-end;
            align-items: center;

            gap: 0.75rem;

            min-height: 50px;
        }

        .nav-link {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 0.55rem 1.25rem;

            border: 1px solid rgba(0, 51, 102, 0.28);

            border-radius: 8px;

            text-decoration: none;

            color: var(--azul);

            background-color: var(--blanco);
            box-shadow: 0 3px 10px rgba(0, 37, 74, 0.08);

            font-size: 0.875rem;

            font-weight: 500;

            transition:
                transform 180ms ease,
                background-color 180ms ease,
                color 180ms ease,
                border-color 180ms ease,
                box-shadow 180ms ease;
        }

        .nav-link:hover {
            background-color: var(--azul);

            color: var(--blanco);

            border-color: var(--azul);

            transform: translateY(-2px);

            box-shadow:
                0 6px 15px rgba(0, 51, 102, 0.18);
        }

        .hero-section {
            flex: 1;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            text-align: center;

            padding: 3rem 1rem;
        }

        .welcome-panel {
            width: min(100%, 760px);
            margin-top: 1.35rem;
            padding: 1.7rem 2rem 1.8rem;
            border: 1px solid rgba(0, 51, 102, 0.2);
            border-top: 4px solid var(--amarillo);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 14px 34px rgba(0, 37, 74, 0.1);
            backdrop-filter: blur(10px);
        }

        .logo-wrapper {
            display: flex;

            align-items: center;
            justify-content: center;

            width: min(500px, 80vw);

            margin-bottom: 2rem;

            animation: aparecer 700ms ease-out;
        }

        .logo-wrapper img {
            display: block;

            width: 100%;

            max-width: 500px;

            height: auto;

            object-fit: contain;

            transition:
                transform 300ms ease,
                filter 300ms ease;
        }

        .logo-wrapper img:hover {
            transform: translateY(-5px) scale(1.02);

            filter:
                drop-shadow(
                    0 12px 18px rgba(0, 51, 102, 0.15)
                );
        }

        p.subtitle {
            max-width: 650px;
            margin: 0 auto;
            color: var(--azul);
            font-size: 1.05rem;
            font-weight: 500;
            line-height: 1.65;
        }

        .subtitle-highlight {
            color: var(--amarillo-texto);
            font-weight: 700;
        }

        .institutional-line {
            width: 70px;

            height: 4px;

            margin: 1.1rem auto 0;

            background-color: var(--amarillo);

            border-radius: 20px;
        }

        .actions {
            display: flex;

            justify-content: center;
            align-items: center;

            gap: 1rem;

            margin-top: 1.4rem;
        }

        .btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-width: 150px;

            padding: 0.7rem 1.5rem;

            border-radius: 8px;

            text-decoration: none;

            font-size: 0.9rem;

            font-weight: 600;

            cursor: pointer;

            transition:
                transform 180ms ease,
                background-color 180ms ease,
                color 180ms ease,
                border-color 180ms ease,
                box-shadow 180ms ease;
        }

        .btn-primary {
            background-color: var(--azul);

            color: var(--blanco);

            border: 1px solid var(--azul);

            box-shadow:
                0 5px 13px rgba(0, 51, 102, 0.22);
        }

        .btn-primary:hover {
            background-color: var(--amarillo);

            border-color: var(--amarillo);

            color: var(--azul);

            transform: translateY(-3px);

            box-shadow:
                0 8px 18px rgba(0, 51, 102, 0.20);
        }

        .btn-primary:active {
            transform: translateY(0) scale(0.97);
        }

        .btn-secondary {
            background-color: var(--blanco);

            color: var(--azul);

            border: 1px solid rgba(0, 51, 102, 0.34);
            box-shadow: 0 3px 9px rgba(0, 37, 74, 0.08);
        }

        .btn-secondary:hover {
            background-color: var(--azul);

            color: var(--blanco);

            border-color: var(--azul);

            transform: translateY(-3px);

            box-shadow:
                0 8px 18px rgba(0, 51, 102, 0.15);
        }

        .btn-secondary:active {
            transform: translateY(0) scale(0.97);
        }

        footer {
            text-align: center;

            padding-top: 1rem;

            color: #40566c;

            font-size: 0.8rem;
        }

        @keyframes aparecer {

            from {
                opacity: 0;

                transform:
                    translateY(20px)
                    scale(0.98);
            }

            to {
                opacity: 1;

                transform:
                    translateY(0)
                    scale(1);
            }

        }

        @media (max-width: 600px) {

            .main-container {
                padding: 1rem;
            }

            .header-nav {
                justify-content: center;
            }

            .hero-section {
                padding: 2rem 0.5rem;
            }

            .welcome-panel {
                padding: 1.4rem 1.1rem 1.5rem;
                border-radius: 10px;
            }

            .logo-wrapper {
                width: 85vw;

                margin-bottom: 1.5rem;
            }

            .actions {
                flex-direction: column;

                width: 100%;

                gap: 0.75rem;
            }

            .btn {
                width: 100%;

                max-width: 280px;
            }

            p.subtitle {
                font-size: 0.95rem;
            }

            .background-photo {
                width: 85%;

                right: -25%;

                opacity: 0.18;
            }

        }

    </style>

</head>

<body>

    <div class="background-photo"></div>

    <div class="main-container">

        <main class="hero-section">

            <div class="logo-wrapper">

                <img
                    src="{{ asset('images/GestiONsolologo.png') }}"
                    alt="Logo de GestiON"
                >

            </div>

            <section class="welcome-panel" aria-label="Acceso al sistema">
                <p class="subtitle">
                    La <span class="subtitle-highlight">plataforma central</span> para el Instituto Adventista Superior de Misiones.
                </p>

                <div class="institutional-line"></div>

                <div class="actions">

                    <a href="{{ route('login') }}" class="btn btn-primary">
                        Iniciar Sesión
                    </a>

                    <a href="{{ route('register') }}" class="btn btn-secondary">
                        Registrarse
                    </a>

                </div>
            </section>

        </main>

        <footer>
            GestiON · Gestión de Eventos
        </footer>

    </div>

</body>

</html>