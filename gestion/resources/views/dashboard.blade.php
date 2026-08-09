@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="container-fluid">

<div class="row mb-4">

    <div class="col-md-4">
        <div class="card dashboard-card mb-3">
            <div class="card-body">
                <h5 class="card-title">Usuarios Registrados</h5>
                <p class="card-text fs-2 fw-bold">
                    {{ $totalUsuarios }}
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card dashboard-card dashboard-card-yellow mb-3">
            <div class="card-body">
                <h5 class="card-title">Turnos para Hoy</h5>
                <p class="card-text fs-2 fw-bold">
                    {{ $turnosHoy }}
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card dashboard-card mb-3">
            <div class="card-body">
                <h5 class="card-title">Espacios Disponibles</h5>
                <p class="card-text fs-2 fw-bold">
                    {{ $totalEspacios }}
                </p>
            </div>
        </div>
    </div>

</div>

<div class="card dashboard-turnos">

    <div class="card-header fs-5 fw-bold">
        Próximos Turnos
    </div>

    <div class="card-body">

        @forelse ($proximosTurnos as $turno)

            <div class="d-flex justify-content-between align-items-center p-3 border-bottom turno-item">

                <div>
                    <strong class="d-block">
                        {{ $turno->actividad->name ?? 'Actividad no definida' }}
                    </strong>

                    <span class="text-muted">
                        Usuario: {{ $turno->user->name ?? 'N/A' }}
                        |
                        En: {{ $turno->subEspacio->name ?? 'N/A' }}
                    </span>
                </div>

                <div class="text-end">

                    <strong class="d-block">
                        {{ $turno->start_time->format('d/m/Y') }}
                    </strong>

                    <span class="badge badge-institucional">
                        {{ $turno->start_time->format('H:i') }} hs
                    </span>

                </div>

            </div>

        @empty

            <p class="text-center text-muted p-3">
                No hay turnos programados próximamente.
            </p>

        @endforelse

    </div>

</div>


</div>

@endsection
