@extends('layouts.app')

@section('title', 'Implementos — GestiON')

@section('content')
<div class="editorial-page inventory-admin-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> ADMINISTRACIÓN</p>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1>Implementos</h1>
                <p>Existencias y disponibilidad del inventario.</p>
            </div>
            <div class="inventory-header-actions">
                <a href="{{ route('admin.recursos.importar') }}" class="action-link action-link-secondary">
                    <i class="fa-solid fa-file-import" aria-hidden="true"></i> Importar Excel
                </a>
                <a href="{{ route('admin.recursos.create') }}" class="action-link action-link-primary">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Agregar implemento
                </a>
            </div>
        </div>
    </header>

    @include('partials._session-status')

    <form class="inventory-filters" action="{{ route('admin.recursos.index') }}" method="GET">
        <div class="inventory-filter-search">
            <label for="buscar">Buscar implementos</label>
            <div class="inventory-search-control">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input class="form-control" id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] }}"
                       placeholder="Nombre, categoría o descripción" maxlength="120">
            </div>
        </div>
        <div>
            <label for="tipo">Categoría</label>
            <select class="form-select" id="tipo" name="tipo">
                <option value="">Todas</option>
                @foreach($tipos as $tipo)
                    <option value="{{ $tipo }}" @selected($filtros['tipo'] === $tipo)>{{ $tipo }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="estado">Disponibilidad</label>
            <select class="form-select" id="estado" name="estado">
                <option value="">Todos</option>
                <option value="disponible" @selected($filtros['estado'] === 'disponible')>Disponible</option>
                <option value="sin_unidades" @selected($filtros['estado'] === 'sin_unidades')>Sin unidades</option>
            </select>
        </div>
        <div class="inventory-filter-actions">
            <button class="action-link action-link-primary" type="submit">Buscar</button>
            <a class="action-link action-link-secondary" href="{{ route('admin.recursos.index') }}">Limpiar</a>
        </div>
    </form>

    @if($recursos->isEmpty())
        <div class="inventory-table-empty">
            <i class="fa-solid fa-cubes-stacked" aria-hidden="true"></i>
            <p class="mb-0">{{ $filtros['buscar'] || $filtros['tipo'] || $filtros['estado'] ? 'No hay implementos que coincidan con la búsqueda.' : 'Todavía no hay implementos registrados.' }}</p>
        </div>
    @else
        <div class="inventory-table-scroll" role="region" aria-label="Inventario de implementos" tabindex="0">
            <table class="inventory-table">
                <thead>
                    <tr>
                        @foreach(['name' => 'Implemento', 'tipo' => 'Categoría', 'cantidad_total' => 'Total', 'cantidad_disponible' => 'Disponible', 'estado' => 'Estado'] as $campo => $etiqueta)
                            @php
                                $activo = $filtros['orden'] === $campo;
                                $nextDirection = $activo && $filtros['direccion'] === 'asc' ? 'desc' : 'asc';
                                $sortUrl = request()->fullUrlWithQuery(['orden' => $campo, 'direccion' => $nextDirection, 'page' => 1]);
                            @endphp
                            <th scope="col" class="{{ in_array($campo, ['cantidad_total', 'cantidad_disponible'], true) ? 'is-numeric' : '' }}"
                                @if($activo) aria-sort="{{ $filtros['direccion'] === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                <a href="{{ $sortUrl }}" class="inventory-sort-link">
                                    {{ $etiqueta }}
                                    @if($activo)
                                        <i class="fa-solid fa-arrow-{{ $filtros['direccion'] === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @else
                                        <i class="fa-solid fa-sort" aria-hidden="true"></i>
                                    @endif
                                    @if($activo)
                                        <span class="visually-hidden">Orden {{ $filtros['direccion'] === 'asc' ? 'ascendente' : 'descendente' }}</span>
                                    @endif
                                </a>
                            </th>
                        @endforeach
                        <th scope="col" class="is-numeric">En uso</th>
                        <th scope="col"><span class="visually-hidden">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recursos as $recurso)
                        <tr>
                            <th scope="row">
                                <span class="inventory-table-name">{{ $recurso->name }}</span>
                                @if($recurso->descripcion)
                                    <span class="inventory-table-description" title="{{ $recurso->descripcion }}">{{ $recurso->descripcion }}</span>
                                @endif
                            </th>
                            <td>{{ $recurso->tipo }}</td>
                            <td class="is-numeric">{{ number_format($recurso->cantidad_total) }}</td>
                            <td class="is-numeric"><strong>{{ number_format($recurso->cantidad_disponible) }}</strong></td>
                            <td>
                                <span class="inventory-table-status {{ $recurso->cantidad_disponible > 0 ? 'is-available' : 'is-empty' }}">
                                    {{ $recurso->estadoInventario }}
                                </span>
                            </td>
                            <td class="is-numeric">{{ number_format($recurso->cantidadEnUso) }}</td>
                            <td class="inventory-row-actions">
                                <a href="{{ route('admin.recursos.edit', $recurso) }}" aria-label="Editar {{ $recurso->name }}">Editar</a>
                                <form action="{{ route('admin.recursos.destroy', $recurso) }}" method="POST"
                                      onsubmit="return confirm('¿Eliminar este recurso? Solo se puede eliminar si no tiene reservas ni historial.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Eliminar {{ $recurso->name }}">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="inventory-table-footer">
            <form id="inventory-page-size" action="{{ route('admin.recursos.index') }}" method="GET">
                <label for="por_pagina">Filas por página</label>
                <select id="por_pagina" name="por_pagina" aria-label="Filas por página">
                    @foreach([10, 25, 50, 100] as $pageSize)
                        <option value="{{ $pageSize }}" @selected($filtros['por_pagina'] === $pageSize)>{{ $pageSize }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="buscar" value="{{ $filtros['buscar'] }}">
                <input type="hidden" name="tipo" value="{{ $filtros['tipo'] }}">
                <input type="hidden" name="estado" value="{{ $filtros['estado'] }}">
                <input type="hidden" name="orden" value="{{ $filtros['orden'] }}">
                <input type="hidden" name="direccion" value="{{ $filtros['direccion'] }}">
            </form>
            <span class="inventory-result-count">
                Mostrando {{ $recursos->firstItem() }}–{{ $recursos->lastItem() }} de {{ number_format($recursos->total()) }} implementos
            </span>
            <div class="inventory-pagination">{{ $recursos->links() }}</div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('por_pagina')?.addEventListener('change', function () {
        document.getElementById('inventory-page-size').requestSubmit();
    });
</script>
@endpush
