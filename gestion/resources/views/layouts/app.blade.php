<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GestiON - ISAM')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    @stack('styles')
</head>
<body class="app-shell">
    <div class="background-photo app-background-photo" aria-hidden="true"></div>
    <header class="app-chrome">
        <a class="app-brand" href="{{ route('dashboard') }}">
            <img class="app-brand-logo" src="{{ asset('images/GestiONsolologo.png') }}" alt="GestiON · ISAM">
        </a>

        <nav class="floating-nav" aria-label="Navegación principal">
            <a class="floating-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
               href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <i class="fa-solid fa-house" aria-hidden="true"></i><span>Inicio</span>
            </a>
            @can('es-admin')
                <a class="floating-nav-link {{ request()->routeIs('eventos.index', 'admin.eventos.*') ? 'is-active' : '' }}"
                   href="{{ route('eventos.index') }}" @if(request()->routeIs('eventos.index', 'admin.eventos.*')) aria-current="page" @endif>
                    <i class="fa-regular fa-calendar" aria-hidden="true"></i><span>Eventos</span>
                </a>
                <a class="floating-nav-link {{ request()->routeIs('solicitudes.*', 'admin.solicitudes.*') ? 'is-active' : '' }}"
                   href="{{ route('solicitudes.index') }}" @if(request()->routeIs('solicitudes.*', 'admin.solicitudes.*')) aria-current="page" @endif>
                    <i class="fa-regular fa-calendar" aria-hidden="true"></i><span>Solicitudes</span>
                </a>
                <a class="floating-nav-link {{ request()->routeIs('calendario.*') ? 'is-active' : '' }}"
                   href="{{ route('calendario.index') }}" @if(request()->routeIs('calendario.*')) aria-current="page" @endif>
                    <i class="fa-regular fa-calendar-days" aria-hidden="true"></i><span>Calendario</span>
                </a>
                <a class="floating-nav-link {{ request()->routeIs('historial.*') ? 'is-active' : '' }}"
                   href="{{ route('historial.index') }}" @if(request()->routeIs('historial.*')) aria-current="page" @endif>
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>Historial</span>
                </a>
                <a class="floating-nav-link {{ request()->routeIs('admin.recursos.*') ? 'is-active' : '' }}"
                   href="{{ route('admin.recursos.index') }}" @if(request()->routeIs('admin.recursos.*')) aria-current="page" @endif>
                    <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><span>Inventario</span>
                </a>
            @elsecan('es-profesor')
                <a class="floating-nav-link {{ request()->routeIs('solicitudes.*') ? 'is-active' : '' }}"
                   href="{{ route('solicitudes.index') }}" @if(request()->routeIs('solicitudes.*')) aria-current="page" @endif>
                    <i class="fa-regular fa-calendar" aria-hidden="true"></i><span>Mis solicitudes</span>
                </a>
                <a class="floating-nav-link {{ request()->routeIs('calendario.*') ? 'is-active' : '' }}"
                   href="{{ route('calendario.index') }}" @if(request()->routeIs('calendario.*')) aria-current="page" @endif>
                    <i class="fa-regular fa-calendar-days" aria-hidden="true"></i><span>Calendario</span>
                </a>
                <a class="floating-nav-link {{ request()->routeIs('historial.*') ? 'is-active' : '' }}"
                   href="{{ route('historial.index') }}" @if(request()->routeIs('historial.*')) aria-current="page" @endif>
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>Historial</span>
                </a>
            @elsecan('es-usuario')
                <a class="floating-nav-link floating-nav-primary {{ request()->routeIs('eventos.index') ? 'is-active' : '' }}"
                   href="{{ route('eventos.index') }}" @if(request()->routeIs('eventos.index')) aria-current="page" @endif>
                    <i class="fa-regular fa-calendar" aria-hidden="true"></i><span>Ver eventos</span>
                </a>
                <a class="floating-nav-link {{ request()->routeIs('calendario.*') ? 'is-active' : '' }}"
                   href="{{ route('calendario.index') }}" @if(request()->routeIs('calendario.*')) aria-current="page" @endif>
                    <i class="fa-regular fa-calendar-days" aria-hidden="true"></i><span>Calendario</span>
                </a>
                <a class="floating-nav-link floating-nav-resource {{ request()->routeIs('solicitudes-recursos.*') ? 'is-active' : '' }}"
                   href="{{ route('solicitudes-recursos.index') }}" @if(request()->routeIs('solicitudes-recursos.*')) aria-current="page" @endif>
                    <i class="fa-solid fa-box-open" aria-hidden="true"></i><span>Mis implementos</span>
                </a>
            @else
                <a class="floating-nav-link {{ request()->routeIs('eventos.index') ? 'is-active' : '' }}"
                   href="{{ route('eventos.index') }}" @if(request()->routeIs('eventos.index')) aria-current="page" @endif>
                    <i class="fa-regular fa-calendar" aria-hidden="true"></i><span>Eventos</span>
                </a>
                <a class="floating-nav-link {{ request()->routeIs('calendario.*') ? 'is-active' : '' }}"
                   href="{{ route('calendario.index') }}" @if(request()->routeIs('calendario.*')) aria-current="page" @endif>
                    <i class="fa-regular fa-calendar-days" aria-hidden="true"></i><span>Calendario</span>
                </a>
            @endcan
        </nav>

        <div class="app-account">
            <span class="app-account-name">{{ auth()->user()->name }}</span>
            <a class="app-logout app-profile-link" href="{{ route('profile.edit') }}">
                <i class="fa-regular fa-user" aria-hidden="true"></i><span>Mi cuenta</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="app-logout" type="submit">
                    <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Cerrar sesión</span>
                </button>
            </form>
        </div>
    </header>

    <main class="app-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
