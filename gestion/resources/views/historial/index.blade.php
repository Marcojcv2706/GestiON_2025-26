@extends('layouts.app')

@section('title', 'Memoria institucional — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> REGISTRO</p>
        <h1>Historial de eventos</h1>
    </header>

    <form class="history-filters" action="{{ route('historial.index') }}" method="GET">
        <div class="history-filter-search">
            <label for="historial-buscar">Buscar en el historial</label>
            <input class="form-control" id="historial-buscar" name="buscar" type="search" value="{{ $filtros['buscar'] }}"
                   placeholder="Evento, espacio, recurso o persona" maxlength="120">
        </div>
        <div>
            <label for="historial-desde">Desde</label>
            <input class="form-control" id="historial-desde" name="desde" type="date" value="{{ $filtros['desde'] }}">
        </div>
        <div>
            <label for="historial-hasta">Hasta</label>
            <input class="form-control" id="historial-hasta" name="hasta" type="date" value="{{ $filtros['hasta'] }}">
        </div>
        @if($isAdmin)
            <div>
                <label for="historial-usuario">Persona</label>
                <select class="form-select" id="historial-usuario" name="usuario_id">
                    <option value="">Todas</option>
                    @foreach($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" @selected($filtros['usuario_id'] === $usuario->id)>{{ $usuario->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="history-filter-actions">
            <button class="action-link action-link-primary" type="submit">Filtrar</button>
            <a class="action-link action-link-secondary" href="{{ route('historial.index') }}">Limpiar</a>
        </div>
    </form>

    @if($historiales->isEmpty())
        <div class="history-empty">
            <i class="fa-regular fa-bookmark" aria-hidden="true"></i>
            <h2 class="h5">{{ $filtros['buscar'] || $filtros['desde'] || $filtros['hasta'] || $filtros['usuario_id'] ? 'No se encontraron registros.' : 'La historia empieza aquí.' }}</h2>
            <p class="mb-0">{{ $filtros['buscar'] || $filtros['desde'] || $filtros['hasta'] || $filtros['usuario_id'] ? 'Prueba con otros filtros.' : 'Los registros aparecerán cuando se incorporen actividades realizadas.' }}</p>
        </div>
    @else
        <div class="history-timeline">
            @foreach($historiales as $historial)
                <article class="history-entry" data-reveal>
                    <div class="history-entry-date">
                        <strong>{{ $historial->fecha_realizacion?->locale('es')->translatedFormat('d M Y') ?? 'Fecha no registrada' }}</strong>
                        {{ $historial->fecha_realizacion?->format('H:i') }}
                    </div>
                    <div class="history-entry-marker" aria-hidden="true"><span></span></div>
                    <div class="history-entry-content">
                        <h2>{{ $historial->evento_nombre }}</h2>
                        @if($historial->descripcion)
                            <p class="history-entry-description">{{ $historial->descripcion }}</p>
                        @endif
                        <div class="history-entry-meta">
                            <span><i class="fa-regular fa-building" aria-hidden="true"></i>{{ $historial->espacio_nombre ?? 'Espacio no especificado' }}</span>
                            <span><i class="fa-regular fa-user" aria-hidden="true"></i>Responsable: {{ $historial->responsable?->name ?? 'No registrado' }}</span>
                            <span><i class="fa-regular fa-paper-plane" aria-hidden="true"></i>Solicitante: {{ $historial->solicitante?->name ?? 'No registrado' }}</span>
                        </div>
                        @if($historial->recursos->isNotEmpty())
                            <div class="history-resources" aria-label="Recursos utilizados">
                                @foreach($historial->recursos as $recurso)
                                    <span class="history-resource">{{ $recurso->recurso_nombre }} <strong>×{{ $recurso->cantidad }}</strong></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        <div class="history-pagination">
            <span>Mostrando {{ $historiales->firstItem() }}–{{ $historiales->lastItem() }} de {{ number_format($historiales->total()) }} registros</span>
            {{ $historiales->links() }}
        </div>
    @endif
</div>
@endsection
