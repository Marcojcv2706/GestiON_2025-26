@extends('layouts.app')

@section('title', 'Solicitudes — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> {{ $isAdmin ? 'REVISIÓN ADMINISTRATIVA' : ($isTeacher ? 'GESTIÓN Y SEGUIMIENTO' : 'SEGUIMIENTO') }}</p>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1>{{ $isAdmin ? 'Solicitudes' : ($isTeacher ? 'Solicitudes' : 'Mis solicitudes') }}</h1>
                <p>{{ $isAdmin ? 'Revisa solicitudes de eventos e implementos en un solo lugar.' : 'Gestiona las solicitudes pendientes de implementos y consulta las que ya revisaste.' }}</p>
            </div>
            <div class="form-actions">
                @can('es-profesor')
                    <a class="action-link action-link-secondary request-primary-cta" href="{{ route('solicitudes.create') }}">
                        <i class="fa-regular fa-calendar-plus" aria-hidden="true"></i><span>Solicitar un evento</span>
                    </a>
                @endcan
                @can('es-usuario')
                    <a class="action-link action-link-primary request-primary-cta" href="{{ route('solicitudes-recursos.create') }}">
                        <i class="fa-solid fa-box-open" aria-hidden="true"></i><span>Solicitar implementos</span>
                    </a>
                @endcan
            </div>
        </div>
    </header>

    @include('partials._session-status')

    <form method="GET" action="{{ route('solicitudes.index') }}" class="request-filters">
        @if($isAdmin)
            <div>
                <label class="form-label" for="tipo">Tipo de solicitud</label>
                <select class="form-select" id="tipo" name="tipo">
                    <option value="todas" @selected($tipo === 'todas')>Todas</option>
                    <option value="eventos" @selected($tipo === 'eventos')>Eventos</option>
                    <option value="implementos" @selected($tipo === 'implementos')>Implementos</option>
                </select>
            </div>
        @endif
        <div>
            <label class="form-label" for="estado">Estado</label>
            <select class="form-select" id="estado" name="estado">
                <option value="">Todos los estados</option>
                @foreach([
                    'pendiente' => 'Pendiente',
                    'aprobada' => 'Aprobada',
                    'rechazada' => 'Rechazada',
                    'devuelta' => 'Devuelta',
                    'modificacion_solicitada' => 'Modificación solicitada (anterior)',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected($estado === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="request-filter-actions">
            <button class="action-link action-link-primary" type="submit">Aplicar filtros</button>
            <a class="action-link action-link-secondary" href="{{ route('solicitudes.index') }}">Limpiar</a>
        </div>
    </form>

    @if($solicitudes->isEmpty())
        <div class="history-empty">
            <i class="fa-regular fa-rectangle-list" aria-hidden="true"></i>
            <h2 class="h5">No hay solicitudes para mostrar.</h2>
        </div>
    @else
        <div class="activity-list">
            @foreach($solicitudes as $item)
                @php($solicitud = $item->solicitud)
                <article class="activity-row unified-request-row">
                    <div class="activity-date">
                        @if($item->tipo === 'evento')
                            <span>{{ $solicitud->start_time->locale('es')->translatedFormat('M') }}</span>
                            <strong>{{ $solicitud->start_time->format('d') }}</strong>
                            <small>{{ $solicitud->start_time->format('H:i') }}</small>
                        @else
                            <span>{{ $solicitud->fecha_uso->locale('es')->translatedFormat('M') }}</span>
                            <strong>{{ $solicitud->fecha_uso->format('d') }}</strong>
                            <small>uso</small>
                        @endif
                    </div>
                    <div class="activity-main">
                        <div class="unified-request-meta">
                            <span class="request-type-badge request-type-{{ $item->tipo }}">{{ $item->tipo === 'evento' ? 'EVENTO' : 'IMPLEMENTO' }}</span>
                            <span class="activity-kicker">{{ $solicitud->user->name }}</span>
                        </div>
                        @if($item->tipo === 'evento')
                            <h2>{{ $solicitud->name }}</h2>
                            @php($subspaceNames = $solicitud->subEspacios->pluck('name'))
                            @if($subspaceNames->isEmpty() && $solicitud->subEspacio)
                                @php($subspaceNames = collect([$solicitud->subEspacio->name]))
                            @endif
                            <p>
                                {{ $solicitud->start_time->locale('es')->translatedFormat('d F Y') }}
                                · {{ $solicitud->start_time->format('H:i') }}–{{ $solicitud->end_time->format('H:i') }}
                                · {{ $solicitud->espacio?->name ?? 'Espacio no especificado' }}
                                @if($subspaceNames->isNotEmpty()) · {{ $subspaceNames->implode(' + ') }} @endif
                            </p>
                            @if($solicitud->description)<p>{{ $solicitud->description }}</p>@endif
                            @php($detailUrl = route('solicitudes.show', $solicitud))
                        @else
                            <h2>Implementos solicitados</h2>
                            <p>Uso previsto: {{ $solicitud->fecha_uso->locale('es')->translatedFormat('d F Y') }}</p>
                            <ul class="unified-request-resources">
                                @foreach($solicitud->recursos as $recurso)
                                    <li>{{ $recurso->name }} ×{{ $recurso->pivot->cantidad }}</li>
                                @endforeach
                            </ul>
                            <p>{{ $solicitud->motivo }}</p>
                            @php($detailUrl = route('solicitudes-recursos.show', $solicitud))
                        @endif
                        <small class="unified-request-created">Solicitada el {{ $solicitud->created_at->locale('es')->translatedFormat('d F Y, H:i') }}</small>
                    </div>
                    <div class="activity-state">
                        @php($statusLabels = [
                            'pendiente' => 'Pendiente',
                            'aprobada' => 'Aprobada',
                            'rechazada' => 'Rechazada',
                            'devuelta' => 'Devuelta',
                            'modificacion_solicitada' => 'Modificación solicitada (anterior)',
                        ])
                        <x-gestion.status-badge :label="$statusLabels[$solicitud->estado] ?? ucfirst($solicitud->estado)" :state="$solicitud->estado" />
                        <a class="action-link action-link-secondary" href="{{ $detailUrl }}">Ver detalles</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $solicitudes->links() }}</div>
    @endif
</div>
@endsection
