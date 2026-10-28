@extends('layouts.app')

@section('title', $isAdmin ? 'Solicitudes de implementos — GestiON' : 'Mis implementos — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> {{ $isAdmin ? 'INVENTARIO' : 'IMPLEMENTOS' }}</p>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1>{{ $isAdmin ? 'Solicitudes de implementos' : 'Mis solicitudes de implementos' }}</h1>
                <p>{{ $isAdmin ? 'Revisa las solicitudes y registra las devoluciones.' : 'Consulta el estado de tus solicitudes.' }}</p>
            </div>
            @unless($isAdmin)
                <a class="action-link action-link-primary request-primary-cta" href="{{ route('solicitudes-recursos.create') }}">
                    <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                    <span>Solicitar implementos</span>
                </a>
            @endunless
        </div>
    </header>

    @include('partials._session-status')

    @if($solicitudes->isEmpty())
        <div class="history-empty">
            <i class="fa-solid fa-box-open" aria-hidden="true"></i>
            <h2 class="h5">Todavía no hay solicitudes.</h2>
            @unless($isAdmin)
                <p class="mb-0">Cuando solicites implementos, podrás consultar aquí su estado.</p>
            @endunless
        </div>
    @else
        <div class="resource-request-list">
            @foreach($solicitudes as $solicitud)
                <article class="resource-request-item">
                    <header class="resource-request-heading">
                        <div>
                            @if($isAdmin)
                                <span class="resource-request-user">{{ $solicitud->user->name }}</span>
                            @endif
                            <h2>Uso previsto: {{ $solicitud->fecha_uso->locale('es')->translatedFormat('d F Y') }}</h2>
                        </div>
                        <x-gestion.status-badge
                            :label="[
                                'pendiente' => 'Pendiente',
                                'aprobada' => 'Aprobada · pendiente de devolución',
                                'rechazada' => 'Rechazada',
                                'devuelta' => 'Devuelta',
                            ][$solicitud->estado] ?? ucfirst($solicitud->estado)"
                            :state="$solicitud->estado"
                        />
                    </header>

                    <p class="resource-request-reason">{{ $solicitud->motivo }}</p>
                    <ul class="resource-request-lines">
                        @foreach($solicitud->recursos as $recurso)
                            <li><strong>{{ $recurso->name }}</strong><span>{{ $recurso->pivot->cantidad }} unidades</span></li>
                        @endforeach
                    </ul>

                    @if($solicitud->observacion_admin)
                        <p class="resource-request-observation"><strong>Motivo:</strong> {{ $solicitud->observacion_admin }}</p>
                    @endif

                    @can('es-admin')
                        <div class="resource-request-actions">
                            @if($solicitud->estado === \App\Models\SolicitudRecurso::PENDIENTE)
                                <form action="{{ route('admin.solicitudes-recursos.aprobar', $solicitud) }}" method="POST">
                                    @csrf
                                    <button class="action-link action-link-primary" type="submit">Aprobar y reservar</button>
                                </form>
                                <form class="resource-request-reject" action="{{ route('admin.solicitudes-recursos.rechazar', $solicitud) }}" method="POST">
                                    @csrf
                                    <label for="motivo-{{ $solicitud->id }}">Motivo del rechazo</label>
                                    <input class="form-control" id="motivo-{{ $solicitud->id }}" name="observacion_admin" maxlength="2000" placeholder="Motivo del rechazo" required>
                                    <button class="action-link action-link-danger" type="submit">Rechazar</button>
                                </form>
                            @elseif($solicitud->estado === \App\Models\SolicitudRecurso::APROBADA)
                                <form action="{{ route('admin.solicitudes-recursos.devolver', $solicitud) }}" method="POST"
                                      onsubmit="return confirm('¿Confirmas que recibiste los implementos devueltos?');">
                                    @csrf
                                    <button class="action-link action-link-primary" type="submit">Registrar devolución</button>
                                </form>
                            @endif
                        </div>
                    @endcan
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $solicitudes->links() }}</div>
    @endif
</div>
@endsection
