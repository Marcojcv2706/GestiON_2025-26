@extends('layouts.app')

@section('title', 'Eventos — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> AGENDA INSTITUCIONAL</p>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1>Eventos</h1>
                <p>{{ $isAdmin ? 'Agenda y reservas de recursos.' : 'Consulta las próximas actividades y sus horarios.' }}</p>
            </div>
            @if($isAdmin)
                <a href="{{ route('admin.eventos.create') }}" class="action-link action-link-primary">
                    Agregar evento <i class="fa-solid fa-plus" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </header>

    @include('partials._session-status')

    @if($eventos->isEmpty())
        <div class="history-empty">
            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
            <h2 class="h5">Todavía no hay eventos registrados.</h2>
            <p class="mb-0">Las próximas actividades aparecerán aquí.</p>
        </div>
    @else
        <div class="activity-list">
            @foreach($eventos as $evento)
                <article class="activity-row" data-reveal>
                    <div class="activity-date">
                        <span>{{ $evento->start_time->locale('es')->translatedFormat('M') }}</span>
                        <strong>{{ $evento->start_time->format('d') }}</strong>
                        <small>{{ $evento->start_time->format('H:i') }}</small>
                    </div>
                    <div class="activity-main">
                        <span class="activity-kicker">{{ $evento->start_time->locale('es')->translatedFormat('l, d F') }}</span>
                        <h3>{{ $evento->name }}</h3>
                        @if($evento->description)
                            <p>{{ $evento->description }}</p>
                        @endif
                    </div>
                    <div class="activity-meta">
                        @php($subspaceNames = $evento->subEspacios->pluck('name'))
                        @if($subspaceNames->isEmpty() && $evento->subEspacio)
                            @php($subspaceNames = collect([$evento->subEspacio->name]))
                        @endif
                        <span>
                            <i class="fa-regular fa-building" aria-hidden="true"></i>
                            {{ $evento->espacio?->name ?? 'Espacio por confirmar' }}
                            @if($subspaceNames->isNotEmpty()) · {{ $subspaceNames->implode(' + ') }} @endif
                        </span>
                        <span><i class="fa-regular fa-user" aria-hidden="true"></i>{{ $evento->user?->name ?? 'Responsable por confirmar' }}</span>
                        @if($evento->recursos->isNotEmpty())
                            @foreach($evento->recursos as $recurso)
                                <span><i class="fa-solid fa-cubes-stacked" aria-hidden="true"></i>{{ $recurso->name }} ×{{ $recurso->pivot->cantidad }}</span>
                            @endforeach
                        @endif
                        <span><i class="fa-regular fa-clock" aria-hidden="true"></i>Hasta {{ $evento->end_time->format('H:i') }}</span>
                    </div>
                    @if($isAdmin)
                        <div class="activity-state activity-actions">
                            <x-gestion.status-badge :label="ucfirst($evento->estado)" :state="$evento->estado" />
                            @if($evento->estado === 'programado')
                                <a class="action-link action-link-secondary" href="{{ route('admin.eventos.edit', $evento) }}">Editar</a>
                                @if($evento->end_time->isPast())
                                    <form action="{{ route('admin.eventos.finalizar', $evento) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="action-link action-link-primary">Finalizar</button>
                                    </form>
                                @endif
                            @endif
                            <form action="{{ route('admin.eventos.destroy', $evento) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este evento?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-link action-link-danger">Eliminar</button>
                            </form>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $eventos->links() }}</div>
    @endif
</div>
@endsection
