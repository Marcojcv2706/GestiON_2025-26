@extends('layouts.app')

@section('title', 'Memoria institucional — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> REGISTRO</p>
        <h1>Historial de eventos</h1>
    </header>

    @can('es-admin')
        <section class="resource-statistics" data-resource-statistics data-endpoint="{{ route('api.reportes.recursos') }}">
            <div class="resource-statistics-heading">
                <h2>Estadísticas de implementos</h2>
                <p>Uso histórico del inventario, según los registros de eventos realizados.</p>
            </div>

            <form class="resource-statistics-filters" data-statistics-filters>
                <div>
                    <label for="statistics-from">Desde</label>
                    <input class="form-control" id="statistics-from" name="desde" type="date">
                </div>
                <div>
                    <label for="statistics-to">Hasta</label>
                    <input class="form-control" id="statistics-to" name="hasta" type="date">
                </div>
                <div>
                    <label for="statistics-space">Espacio</label>
                    <select class="form-select" id="statistics-space" name="espacio_id">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div>
                    <label for="statistics-subspace">Subespacio</label>
                    <select class="form-select" id="statistics-subspace" name="subespacio_id">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div>
                    <label for="statistics-category">Categoría</label>
                    <select class="form-select" id="statistics-category" name="categoria">
                        <option value="">Todas</option>
                        @foreach(\App\Models\Recurso::CATEGORIAS as $categoria)
                            <option value="{{ $categoria }}">{{ $categoria }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="statistics-resource">Implemento</label>
                    <select class="form-select" id="statistics-resource" name="recurso_id">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div>
                    <label for="statistics-user">Solicitante</label>
                    <select class="form-select" id="statistics-user" name="usuario_id">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div>
                    <label for="statistics-period">Agrupar por</label>
                    <select class="form-select" id="statistics-period" name="periodo">
                        <option value="mes" selected>Mes</option>
                        <option value="semana">Semana</option>
                        <option value="dia">Día</option>
                    </select>
                </div>
                <div class="resource-statistics-actions">
                    <button class="action-link action-link-primary" type="submit">Aplicar filtros</button>
                    <button class="action-link action-link-secondary" type="reset">Limpiar</button>
                </div>
            </form>

            <div class="resource-statistics-status" data-statistics-status role="status" aria-live="polite">
                <span data-statistics-message>Cargando estadísticas…</span>
                <button class="action-link action-link-secondary" type="button" data-statistics-retry hidden>Intentar nuevamente</button>
            </div>

            <div class="resource-statistics-grid">
                <article class="resource-statistics-card is-wide">
                    <h3>Espacios más utilizados</h3>
                    <p>Eventos con implementos registrados.</p>
                    <div class="resource-statistics-chart"><canvas data-chart="spaces" role="img" aria-label="Usos de implementos por espacio"></canvas></div>
                    <div class="resource-statistics-empty" data-chart-empty="spaces" hidden>No hay datos suficientes para generar este gráfico.</div>
                </article>
                <article class="resource-statistics-card is-wide">
                    <h3>Subespacios más utilizados</h3>
                    <p>Top 10 según eventos relacionados.</p>
                    <div class="resource-statistics-chart"><canvas data-chart="subspaces" role="img" aria-label="Usos de implementos por subespacio"></canvas></div>
                    <div class="resource-statistics-empty" data-chart-empty="subspaces" hidden>No hay datos suficientes para generar este gráfico.</div>
                </article>
                <article class="resource-statistics-card">
                    <h3>Implementos más solicitados</h3>
                    <p>Top 10; compara solicitudes y unidades acumuladas.</p>
                    <div class="resource-statistics-chart"><canvas data-chart="resources" role="img" aria-label="Solicitudes y unidades por implemento"></canvas></div>
                    <div class="resource-statistics-empty" data-chart-empty="resources" hidden>No hay datos suficientes para generar este gráfico.</div>
                </article>
                <article class="resource-statistics-card">
                    <h3>Solicitantes principales</h3>
                    <p>Top 10; compara solicitudes y unidades acumuladas.</p>
                    <div class="resource-statistics-chart"><canvas data-chart="users" role="img" aria-label="Solicitudes y unidades por solicitante"></canvas></div>
                    <div class="resource-statistics-empty" data-chart-empty="users" hidden>No hay datos suficientes para generar este gráfico.</div>
                </article>
                <article class="resource-statistics-card">
                    <h3>Uso por categoría</h3>
                    <p>Distribución de solicitudes entre las categorías del inventario.</p>
                    <div class="resource-statistics-chart"><canvas data-chart="categories" role="img" aria-label="Solicitudes por categoría de implemento"></canvas></div>
                    <div class="resource-statistics-empty" data-chart-empty="categories" hidden>No hay datos suficientes para generar este gráfico.</div>
                </article>
                <article class="resource-statistics-card is-wide">
                    <h3>Evolución del uso</h3>
                    <p>Solicitudes de implementos a través del tiempo.</p>
                    <div class="resource-statistics-chart"><canvas data-chart="periods" role="img" aria-label="Evolución temporal de solicitudes de implementos"></canvas></div>
                    <div class="resource-statistics-empty" data-chart-empty="periods" hidden>No hay datos suficientes para generar este gráfico.</div>
                </article>
            </div>
        </section>
    @endcan

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
