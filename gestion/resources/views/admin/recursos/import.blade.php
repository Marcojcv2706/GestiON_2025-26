@extends('layouts.app')

@section('title', 'Importar implementos — GestiON')

@section('content')
<div class="editorial-page inventory-admin-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> INVENTARIO</p>
        <h1>Importar implementos</h1>
        <p>Sube el Excel, revisa las filas y confirma solo cuando no haya errores.</p>
    </header>

    @include('partials._session-status')

    @if($previewExpired)
        <div class="alert alert-warning" role="alert">
            La previsualización ya no está disponible. Sube el archivo nuevamente para continuar.
        </div>
    @endif

    <section class="resource-import-panel">
        <div class="resource-import-help">
            <h2>1. Descarga la plantilla oficial</h2>
            <p>La plantilla contiene las columnas correctas y las categorías permitidas.</p>
            <a class="action-link action-link-secondary" href="{{ route('admin.recursos.plantilla') }}">
                <i class="fa-solid fa-download" aria-hidden="true"></i> Descargar plantilla Excel
            </a>
        </div>

        <form class="resource-import-upload" action="{{ route('admin.recursos.importar.previsualizar') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            <label for="archivo">2. Selecciona el archivo completado (.xlsx o .xls)</label>
            <input class="form-control @error('archivo') is-invalid @enderror" id="archivo" name="archivo"
                   type="file" accept=".xlsx,.xls" required>
            @error('archivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button class="action-link action-link-primary" type="submit">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Validar y previsualizar
            </button>
        </form>

        <p class="resource-import-note">
            Categorías permitidas: <strong>Deportivos</strong>, <strong>Audiovisuales</strong> y <strong>Otros</strong>.
            La disponibilidad se inicializa igual al total; el estado se calcula automáticamente.
        </p>
    </section>

    @if($preview)
        @php
            $canImport = $preview['valid_count'] > 0
                && $preview['error_count'] === 0
                && $preview['global_errors'] === [];
        @endphp
        <section class="resource-import-preview" aria-labelledby="preview-title">
            <div class="resource-import-summary">
                <div>
                    <p class="eyebrow"><span class="eyebrow-line"></span> 3. REVISIÓN</p>
                    <h2 id="preview-title">Vista previa del archivo</h2>
                </div>
                <div class="resource-import-counts">
                    <span class="is-valid">{{ $preview['valid_count'] }} válidas</span>
                    <span class="{{ $preview['error_count'] > 0 || $preview['global_errors'] ? 'is-invalid' : 'is-valid' }}">
                        {{ $preview['error_count'] + count($preview['global_errors']) }} con errores
                    </span>
                </div>
            </div>

            @if($preview['global_errors'])
                <div class="alert alert-danger" role="alert">
                    @foreach($preview['global_errors'] as $field => $message)
                        <p class="mb-1"><strong>{{ $field }}:</strong> {{ $message }}</p>
                    @endforeach
                </div>
            @endif

            @if($preview['rows'])
                <div class="inventory-table-scroll resource-import-table-scroll" role="region"
                     aria-label="Vista previa de implementos importados" tabindex="0">
                    <table class="inventory-table resource-import-table">
                        <thead>
                            <tr>
                                <th scope="col">Fila</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Descripción</th>
                                <th scope="col" class="is-numeric">Cantidad total</th>
                                <th scope="col">Validación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['rows'] as $row)
                                <tr class="{{ $row['errors'] ? 'has-errors' : '' }}">
                                    <td>{{ $row['number'] }}</td>
                                    <td>{{ $row['data']['name'] ?: '—' }}</td>
                                    <td>{{ $row['data']['tipo'] ?: '—' }}</td>
                                    <td>{{ $row['data']['descripcion'] ?: '—' }}</td>
                                    <td class="is-numeric">{{ $row['data']['cantidad_total'] ?: '—' }}</td>
                                    <td>
                                        @if($row['errors'])
                                            <ul class="resource-import-errors">
                                                @foreach($row['errors'] as $field => $messages)
                                                    @foreach((array) $messages as $message)
                                                        <li><strong>{{ [
                                                            'name' => 'Nombre',
                                                            'tipo' => 'Categoría',
                                                            'descripcion' => 'Descripción',
                                                            'cantidad_total' => 'Cantidad total',
                                                        ][$field] ?? ucfirst($field) }}:</strong> {{ $message }}</li>
                                                    @endforeach
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="inventory-table-status is-available">Válido</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($canImport)
                <form class="resource-import-confirm" action="{{ route('admin.recursos.importar.confirmar') }}" method="POST">
                    @csrf
                    <input type="hidden" name="previsualizacion" value="{{ $previewToken }}">
                    <a class="action-link action-link-secondary" href="{{ route('admin.recursos.importar') }}">Subir otro archivo</a>
                    <button class="action-link action-link-primary" type="submit">
                        <i class="fa-solid fa-check" aria-hidden="true"></i> Confirmar importación
                    </button>
                </form>
            @elseif($preview['rows'] || $preview['global_errors'])
                <p class="resource-import-blocked" role="status">
                    Corrige el archivo y vuelve a subirlo. No se guardó ningún implemento.
                </p>
            @endif
        </section>
    @endif

    <a class="resource-import-back" href="{{ route('admin.recursos.index') }}">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver a implementos
    </a>
</div>
@endsection
