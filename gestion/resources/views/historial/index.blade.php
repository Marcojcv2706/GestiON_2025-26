
@extends('layouts.app')

@section('title', 'Historial')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h1 class="mb-1">Historial</h1>
            <p class="text-muted mb-0">
                Registro de actividades realizadas y recursos utilizados.
            </p>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if($historiales->isEmpty())
                <div class="text-center py-5">
                    <h5>No hay registros en el historial</h5>
                    <p class="text-muted mb-0">
                        Los registros aparecerán cuando se incorporen actividades realizadas.
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Actividad</th>
                                <th>Fecha</th>
                                <th>Espacio</th>
                                <th>Responsable</th>
                                <th>Solicitante</th>
                                <th>Recursos utilizados</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historiales as $historial)
                                <tr>
                                    <td>
                                        <strong>{{ $historial->actividad_nombre }}</strong>
                                        @if($historial->descripcion)
                                            <div class="small text-muted">
                                                {{ $historial->descripcion }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $historial->fecha_realizacion?->format('d/m/Y H:i') ?? 'No registrada' }}
                                    </td>

                                    <td>{{ $historial->espacio_nombre ?? 'No especificado' }}</td>

                                    <td>
                                        {{ $historial->responsable?->name ?? 'No registrado' }}
                                    </td>

                                    <td>
                                        {{ $historial->solicitante?->name ?? 'No registrado' }}
                                    </td>

                                    <td>
                                        @forelse($historial->recursos as $item)
                                            <div>
                                                {{ $item->recurso_nombre }}
                                                ({{ $item->cantidad }})
                                            </div>
                                        @empty
                                            <span class="text-muted">Sin recursos registrados</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $historiales->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
