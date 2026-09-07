@extends('layouts.app')

@section('title', 'Listado de Actividades')

@section('content')

<div class="container-fluid">

    <h1 class="mb-4">
        Actividades
    </h1>

    {{-- ESTE BOTÓN SOLO APARECE SI EL USUARIO TIENE PERMISO --}}
    @can('es-admin')
        <a href="{{ route('actividades.create') }}"
           class="btn btn-primary transition-all duration-200 ease-out hover:scale-105 hover:shadow-lg active:scale-95">
            Crear Actividad
        </a>
    @endcan

    <div class="card mt-4">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Sub-espacio</th>
                            <th>Creado por</th>

                            {{-- LA COLUMNA DE ACCIONES SOLO ES VISIBLE PARA ROLES CON PERMISO --}}
                            @can('es-admin')
                                <th>Acciones</th>
                            @endcan
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($actividades as $actividad)
                            <tr>
                                <td>{{ $actividad->name }}</td>

                                <td>
                                    {{ $actividad->subEspacio?->name ?? 'N/A' }}
                                </td>

                                <td>
                                    {{ $actividad->creador?->name ?? 'N/A' }}
                                </td>

                                @can('es-admin')
                                    <td>

                                        {{-- BOTÓN EDITAR --}}
                                        <a href="{{ route('actividades.edit', $actividad) }}"
                                           class="btn btn-sm btn-warning transition-all duration-200 ease-out hover:scale-105 hover:shadow-lg active:scale-95">
                                            Editar
                                        </a>

                                        {{-- BOTÓN ELIMINAR --}}
                                        <form action="{{ route('actividades.destroy', $actividad) }}"
                                              method="POST"
                                              class="d-inline">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="btn btn-sm btn-danger transition-all duration-200 ease-out hover:scale-105 hover:shadow-lg active:scale-95"
                                                    onclick="return confirm('¿Seguro?')">
                                                Eliminar
                                            </button>

                                        </form>

                                    </td>
                                @endcan
                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="text-center">
                                    No hay actividades registradas.
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $actividades->links() }}

        </div>
    </div>

</div>

@endsection