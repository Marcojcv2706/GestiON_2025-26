<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

<title>@yield('title', 'GestiON')</title>

{{-- Vite --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Bootstrap --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

{{-- Font Awesome --}}
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


</head>

<body>

<div class="d-flex" id="wrapper">


{{-- =====================================================
     BARRA LATERAL
====================================================== --}}

<div id="sidebar-wrapper">

    {{-- LOGO / NOMBRE --}}
    <div class="sidebar-heading">
        <div class="sidebar-logo">
            <i class="fas fa-calendar-check"></i>
        </div>

        <span>GestiON</span>
    </div>


    {{-- MENÚ --}}
    <div class="list-group list-group-flush">

        {{-- DASHBOARD --}}
        <a href="{{ route('dashboard') }}"
           class="list-group-item list-group-item-action menu-institucional">
            <i class="fas fa-chart-line me-2"></i>
            Dashboard
        </a>


        {{-- ACTIVIDADES --}}
        <a href="{{ route('actividades.index') }}"
           class="list-group-item list-group-item-action menu-institucional">
            <i class="fas fa-calendar-days me-2"></i>
            Actividades
        </a>


        {{-- TURNOS --}}
        <a href="{{ route('turnos.index') }}"
           class="list-group-item list-group-item-action menu-institucional">
            <i class="fas fa-clock me-2"></i>
            Turnos
        </a>


        {{-- ESPACIOS --}}
        @can('es-admin')
            <a href="{{ route('admin.espacios.index') }}"
               class="list-group-item list-group-item-action menu-institucional ps-5">
                <i class="fas fa-building me-2"></i>
                Espacios
            </a>


            {{-- RECURSOS --}}
            <a href="{{ route('admin.recursos.index') }}"
               class="list-group-item list-group-item-action menu-institucional ps-5">
                <i class="fas fa-boxes-stacked me-2"></i>
                Recursos
            </a>


            {{-- USUARIOS --}}
            <a href="{{ route('admin.usuarios.index') }}"
               class="list-group-item list-group-item-action menu-institucional ps-5">
                <i class="fas fa-users me-2"></i>
                Usuarios
            </a>
        @endcan


        {{-- =================================================
             LOGOUT
        ================================================== --}}

        <form method="POST"
              action="{{ route('logout') }}"
              id="logout-form"
              style="display: none;">
            @csrf
        </form>


        <a href="{{ route('logout') }}"
           class="list-group-item list-group-item-action menu-logout fw-bold"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">

            <i class="fas fa-sign-out-alt me-2"></i>
            Cerrar Sesión

        </a>

    </div>

</div>


{{-- =====================================================
     CONTENIDO PRINCIPAL
====================================================== --}}

<div id="page-content-wrapper">

    {{-- =================================================
         NAVBAR SUPERIOR
    ================================================== --}}

    <nav class="navbar navbar-expand-lg navbar-institucional border-bottom">

        <div class="container-fluid">

            <div class="collapse navbar-collapse">

                <ul class="navbar-nav ms-auto mt-2 mt-lg-0">

                    @auth

                        <li class="nav-item dropdown">

                            <a class="nav-link dropdown-toggle"
                               href="#"
                               id="navbarDropdown"
                               role="button"
                               data-bs-toggle="dropdown"
                               aria-expanded="false">

                                <i class="fas fa-user-circle me-1"></i>
                                {{ auth()->user()->name }}

                            </a>


                            <div class="dropdown-menu dropdown-menu-end">

                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user me-2"></i>
                                    Mi Perfil
                                </a>

                                <div class="dropdown-divider"></div>

                                <form method="POST"
                                      action="{{ route('logout') }}">

                                    @csrf

                                    <a class="dropdown-item"
                                       href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); this.closest('form').submit();">

                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        Cerrar Sesión

                                    </a>

                                </form>

                            </div>

                        </li>

                    @endauth

                </ul>

            </div>

        </div>

    </nav>


    {{-- =================================================
         CONTENIDO
    ================================================== --}}

    <main class="container-fluid p-4">

        {{-- MENSAJE DE ÉXITO --}}
        @if (session('success'))

            <div class="alert alert-success shadow-sm">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
            </div>

        @endif


        {{-- CONTENIDO DE CADA VISTA --}}
        @yield('content')

    </main>

</div>


</div>

{{-- =========================================================
ESTILOS DEL LAYOUT
========================================================= --}}

<style>

    :root {
        --institucional-azul: #003366;
        --institucional-amarillo: #EDC001;
        --institucional-blanco: #FFFFFF;
    }


    /* =====================================================
       ESTRUCTURA
    ====================================================== */

    body {
        margin: 0;
        background-color: #f5f7fa;
    }

    #wrapper {
        min-height: 100vh;
    }


    /* =====================================================
       SIDEBAR
    ====================================================== */

    #sidebar-wrapper {
        width: 250px;
        min-height: 100vh;
        background-color: var(--institucional-azul);
        color: var(--institucional-blanco);
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.08);
        flex-shrink: 0;
    }


    /* =====================================================
       LOGO
    ====================================================== */

    .sidebar-heading {
        height: 70px;
        display: flex;
        align-items: center;
        gap: 12px;

        padding: 0 20px;

        background-color: var(--institucional-azul);

        color: var(--institucional-blanco);

        font-size: 1.35rem;
        font-weight: 700;

        border-bottom: 3px solid var(--institucional-amarillo);
    }


    .sidebar-logo {
        width: 38px;
        height: 38px;

        display: flex;
        align-items: center;
        justify-content: center;

        background-color: var(--institucional-amarillo);
        color: var(--institucional-azul);

        border-radius: 10px;
    }


    /* =====================================================
       MENÚ
    ====================================================== */

    .menu-institucional {
        background-color: var(--institucional-azul) !important;
        color: var(--institucional-blanco) !important;

        border: none !important;

        padding-top: 12px;
        padding-bottom: 12px;

        transition:
            background-color 180ms ease,
            color 180ms ease,
            transform 180ms ease,
            padding-left 180ms ease;
    }


    .menu-institucional:hover {
        background-color: var(--institucional-amarillo) !important;
        color: var(--institucional-azul) !important;

        transform: translateX(4px);
    }


    .menu-institucional i {
        width: 20px;
        text-align: center;
    }


    /* =====================================================
       LOGOUT
    ====================================================== */

    .menu-logout {
        margin-top: 10px;

        background-color: var(--institucional-azul) !important;
        color: var(--institucional-amarillo) !important;

        border-top: 1px solid rgba(255, 255, 255, 0.1) !important;

        padding-top: 14px;
        padding-bottom: 14px;

        transition:
            background-color 180ms ease,
            color 180ms ease,
            transform 180ms ease;
    }


    .menu-logout:hover {
        background-color: var(--institucional-amarillo) !important;
        color: var(--institucional-azul) !important;

        transform: translateX(4px);
    }


    /* =====================================================
       CONTENIDO
    ====================================================== */

    #page-content-wrapper {
        width: 100%;
        min-width: 0;
    }


    /* =====================================================
       NAVBAR
    ====================================================== */

   .navbar-institucional {
       height: 70px;
    box-sizing: border-box;

    background-color: #FFFFFF !important;

    border-bottom:3px solid var(--institucional-amarillo) !important;
    box-shadow: none !important;
}


    .navbar-institucional .nav-link {
        color: var(--institucional-azul) !important;

        font-weight: 600;

        transition:
            color 180ms ease,
            transform 180ms ease;
    }


    .navbar-institucional .nav-link:hover {
        color: var(--institucional-amarillo) !important;

        transform: translateY(-1px);
    }


    /* =====================================================
       DROPDOWN
    ====================================================== */

    .navbar-institucional .dropdown-menu {
        border: none;

        border-top: 3px solid var(--institucional-amarillo);

        box-shadow: 0 8px 20px rgba(0, 51, 102, 0.15);

        border-radius: 0 0 10px 10px;
    }


    .navbar-institucional .dropdown-item {
        color: var(--institucional-azul);

        transition:
            background-color 180ms ease,
            color 180ms ease,
            padding-left 180ms ease;
    }


    .navbar-institucional .dropdown-item:hover {
        background-color: var(--institucional-azul);

        color: var(--institucional-blanco);

        padding-left: 22px;
    }


    /* =====================================================
       ALERTAS
    ====================================================== */

    .alert {
        border: none;
        border-left: 5px solid var(--institucional-amarillo);
        border-radius: 8px;
    }


    /* =====================================================
       RESPONSIVE
    ====================================================== */

    @media (max-width: 768px) {

        #sidebar-wrapper {
            width: 210px;
        }

        .sidebar-heading {
            font-size: 1.1rem;
        }

    }

</style>

{{-- Bootstrap JS --}}

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
