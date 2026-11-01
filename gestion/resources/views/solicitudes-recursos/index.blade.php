@extends('layouts.app')

@section('title', 'Mis solicitudes de implementos — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> SEGUIMIENTO PERSONAL</p>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1>Mis solicitudes de implementos</h1>
                <p>Consulta el estado de tus solicitudes y sus detalles.</p>
            </div>
            <a class="action-link action-link-primary request-primary-cta" href="{{ route('solicitudes-recursos.create') }}">
                <i class="fa-solid fa-box-open" aria-hidden="true"></i><span>Solicitar implementos</span>
            </a>
        </div>
    </header>

    @include('partials._session-status')

    @if($solicitudes->isEmpty())
        <div class="history-empty">
            <i class="fa-regular fa-rectangle-list" aria-hidden="true"></i>
            <h2 class="h5">Todavía no tienes solicitudes de implementos.</h2>
        </div>
    @else
        <div class="activity-list">
            @foreach($solicitudes as $solicitud)
                <article class="activity-row unified-request-row">
                    <div class="activity-date">
                        <span>{{ $solicitud->fecha_uso->locale('es')->translatedFormat('M') }}</span>
                        <strong>{{ $solicitud->fecha_uso->format('d') }}</strong>
                        <small>uso</small>
                    </div>
                    <div class="activity-main">
                        <div class="unified-request-meta">
                            <span class="request-type-badge request-type-implemento">IMPLEMENTO</span>
                            <span class="activity-kicker">Solicitada el {{ $solicitud->created_at->locale('es')->translatedFormat('d F Y, H:i') }}</span>
                        </div>
                        <h2>Implementos solicitados</h2>
                        <ul class="unified-request-resources">
                            @foreach($solicitud->recursos as $recurso)
                                <li>{{ $recurso->name }} ×{{ $recurso->pivot->cantidad }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="activity-state">
                        @php($statusLabels = [
                            'pendiente' => 'Pendiente',
                            'aprobada' => 'Aprobada',
                            'rechazada' => 'Rechazada',
                            'devuelta' => 'Devuelta',
                        ])
                        <x-gestion.status-badge :label="$statusLabels[$solicitud->estado] ?? ucfirst($solicitud->estado)" :state="$solicitud->estado" />
                        <a class="action-link action-link-secondary" href="{{ route('solicitudes-recursos.show', $solicitud) }}">Ver detalles</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $solicitudes->links() }}</div>
    @endif
</div>
@endsection
