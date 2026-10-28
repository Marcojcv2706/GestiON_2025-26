@extends('layouts.app')

@section('title', $isAdmin ? 'Solicitudes — GestiON' : 'Mis solicitudes — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> {{ $isAdmin ? 'REVISIÓN ADMINISTRATIVA' : 'SEGUIMIENTO' }}</p>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1>{{ $isAdmin ? 'Solicitudes' : 'Mis solicitudes' }}</h1>
                @unless($isAdmin)<p>Revisa aquí el estado y las respuestas de tus solicitudes.</p>@endunless
            </div>
            @if($isAdmin)
                <a class="action-link action-link-secondary" href="{{ route('solicitudes-recursos.index') }}">
                    Solicitudes de implementos
                </a>
            @endif
            @can('es-profesor')
                <a class="action-link action-link-primary request-primary-cta" href="{{ route('solicitudes.create') }}">
                    <i class="fa-regular fa-calendar-plus" aria-hidden="true"></i>
                    <span>Solicitar un evento</span>
                </a>
            @endcan
        </div>
    </header>

    @include('partials._session-status')

    @if($isAdmin)
        <form method="GET" class="mb-4">
            <label class="form-label" for="estado">Filtrar estado</label>
            <select class="form-select" id="estado" name="estado" onchange="this.form.submit()">
                <option value="">Todas</option>
                @foreach(['pendiente' => 'Pendientes', 'aprobada' => 'Aprobadas', 'rechazada' => 'Rechazadas', 'modificacion_solicitada' => 'Modificación solicitada'] as $value => $label)
                    <option value="{{ $value }}" @selected($estado === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    @endif

    @if($solicitudes->isEmpty())
        <div class="history-empty">
            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
            <h2 class="h5">No hay solicitudes para mostrar.</h2>
        </div>
    @else
        <div class="activity-list">
            @foreach($solicitudes as $solicitud)
                <article class="activity-row">
                    <div class="activity-date">
                        <span>{{ $solicitud->start_time->locale('es')->translatedFormat('M') }}</span>
                        <strong>{{ $solicitud->start_time->format('d') }}</strong>
                        <small>{{ $solicitud->start_time->format('H:i') }}</small>
                    </div>
                    <div class="activity-main">
                        @if($isAdmin)<span class="activity-kicker">{{ $solicitud->user->name }}</span>@endif
                        <h3>{{ $solicitud->name }}</h3>
                        @php($subspaceNames = $solicitud->subEspacios->pluck('name'))
                        @if($subspaceNames->isEmpty() && $solicitud->subEspacio)
                            @php($subspaceNames = collect([$solicitud->subEspacio->name]))
                        @endif
                        <p>
                            {{ $solicitud->espacio->name }}
                            @if($subspaceNames->isNotEmpty()) · {{ $subspaceNames->implode(' + ') }} @endif
                            · {{ $solicitud->start_time->locale('es')->translatedFormat('d F Y') }}
                        </p>
                        @if($solicitud->observacion_admin)
                            <p><strong>Observación:</strong> {{ $solicitud->observacion_admin }}</p>
                        @endif
                    </div>
                    <div class="activity-state">
                        <x-gestion.status-badge
                            :label="[
                                'pendiente' => 'Pendiente',
                                'aprobada' => 'Aprobada',
                                'rechazada' => 'Rechazada',
                                'modificacion_solicitada' => 'Modificación solicitada',
                            ][$solicitud->estado] ?? ucfirst($solicitud->estado)"
                            :state="$solicitud->estado"
                        />
                        <a class="action-link action-link-secondary" href="{{ route('solicitudes.show', $solicitud) }}">Ver solicitud</a>
                    </div>
                    @if($solicitud->observacion_admin && in_array($solicitud->estado, ['rechazada', 'modificacion_solicitada'], true))
                        <div class="request-next-step">
                            <strong>
                                {{ $solicitud->estado === 'rechazada' ? 'Solicitud rechazada' : 'Se necesitan cambios' }}
                            </strong>
                            <span>Motivo: {{ $solicitud->observacion_admin }}</span>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $solicitudes->links() }}</div>
    @endif
</div>
@endsection
