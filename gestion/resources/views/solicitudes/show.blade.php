@extends('layouts.app')

@section('title', 'Solicitud #'.$solicitud->id.' — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> SOLICITUD #{{ $solicitud->id }}</p>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1>{{ $solicitud->name }}</h1>
                <p>{{ $solicitud->start_time->locale('es')->translatedFormat('l, d F Y') }} · {{ $solicitud->start_time->format('H:i') }}–{{ $solicitud->end_time->format('H:i') }}</p>
            </div>
            <x-gestion.status-badge
                :label="[
                    'pendiente' => 'Pendiente',
                    'aprobada' => 'Aprobada',
                    'rechazada' => 'Rechazada',
                    'modificacion_solicitada' => 'Modificación solicitada',
                ][$solicitud->estado] ?? ucfirst($solicitud->estado)"
                :state="$solicitud->estado"
            />
        </div>
    </header>
    @include('partials._session-status')
    @if($solicitud->observacion_admin)
        <div class="alert alert-warning request-status-message" role="status">
            <strong>
                @if($solicitud->estado === \App\Models\SolicitudEvento::RECHAZADA)
                    Solicitud rechazada.
                @elseif($solicitud->estado === \App\Models\SolicitudEvento::MODIFICACION_SOLICITADA)
                    Se necesitan cambios para continuar.
                @else
                    Nota del equipo{{ $solicitud->revisor ? ' · '.$solicitud->revisor->name : '' }}.
                @endif
            </strong>
            <span>Motivo: {{ $solicitud->observacion_admin }}</span>
            @if($solicitud->estado === \App\Models\SolicitudEvento::MODIFICACION_SOLICITADA)
                @php($lastDecision = $solicitud->decisiones->sortByDesc('id')->first())
                @if($lastDecision?->accion === 'solicitud_corregida')
                    <span>Ya guardaste los cambios. Envíalos nuevamente para que podamos revisarlos.</span>
                @else
                    <span>Abre la solicitud para corregir los datos indicados.</span>
                @endif
            @endif
        </div>
    @endif

    <section class="form-editorial-panel mb-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Solicitante</dt><dd class="col-sm-9">{{ $solicitud->user->name }}</dd>
            <dt class="col-sm-3">Espacio</dt>
            <dd class="col-sm-9">
                {{ $solicitud->espacio->name }}
                @php($subspaceNames = $solicitud->subEspacios->pluck('name'))
                @if($subspaceNames->isEmpty() && $solicitud->subEspacio)
                    @php($subspaceNames = collect([$solicitud->subEspacio->name]))
                @endif
                @if($subspaceNames->isNotEmpty()) · {{ $subspaceNames->implode(' + ') }} @endif
            </dd>
            <dt class="col-sm-3">Descripción</dt><dd class="col-sm-9">{{ $solicitud->description ?: '—' }}</dd>
            <dt class="col-sm-3">Recursos</dt>
            <dd class="col-sm-9">
                @forelse($solicitud->recursos as $recurso)
                    <span class="history-resource">{{ $recurso->name }} <strong>×{{ $recurso->pivot->cantidad }}</strong></span>
                @empty
                    Sin recursos solicitados
                @endforelse
            </dd>
        </dl>
    </section>

    @if(!$isAdmin && $solicitud->estado === \App\Models\SolicitudEvento::MODIFICACION_SOLICITADA)
        @php($lastDecision = $solicitud->decisiones->sortByDesc('id')->first())
        <div class="form-actions mb-4">
            @if($lastDecision?->accion === 'solicitud_corregida')
                <form action="{{ route('solicitudes.reenviar', $solicitud) }}" method="POST">
                    @csrf
                    <button class="action-link action-link-primary request-submit-button" type="submit">Enviar nuevamente</button>
                </form>
            @else
                <a class="action-link action-link-primary" href="{{ route('solicitudes.edit', $solicitud) }}">Revisar y modificar</a>
            @endif
        </div>
    @endif

    @if($solicitud->evento)
        <p><a class="action-link action-link-secondary" href="{{ route('eventos.index') }}">Ver evento aprobado</a></p>
    @endif

    @if($isAdmin && $solicitud->estado === \App\Models\SolicitudEvento::PENDIENTE)
        <section class="form-editorial-panel mb-4">
            <h2 class="h5">Decisión administrativa</h2>
            <div class="form-actions">
                <form action="{{ route('admin.solicitudes.aprobar', $solicitud) }}" method="POST">
                    @csrf
                    <button class="action-link action-link-primary" type="submit">Aprobar</button>
                </form>
            </div>
            <div class="row g-3 mt-2">
                <form class="col-md-6" action="{{ route('admin.solicitudes.solicitar-modificacion', $solicitud) }}" method="POST">
                    @csrf
                    <label class="form-label" for="motivo-modificacion">Motivo de modificación *</label>
                    <textarea class="form-control" id="motivo-modificacion" name="motivo" rows="3" required minlength="5"></textarea>
                    <button class="action-link action-link-secondary mt-2" type="submit">Solicitar modificación</button>
                </form>
                <form class="col-md-6" action="{{ route('admin.solicitudes.rechazar', $solicitud) }}" method="POST">
                    @csrf
                    <label class="form-label" for="motivo-rechazo">Motivo del rechazo *</label>
                    <textarea class="form-control" id="motivo-rechazo" name="motivo" rows="3" required minlength="5"></textarea>
                    <button class="action-link action-link-danger mt-2" type="submit">Rechazar solicitud</button>
                </form>
            </div>
        </section>

        <details class="form-editorial-panel mb-4">
            <summary>Modificar datos de la solicitud</summary>
            <form class="mt-4" action="{{ route('admin.solicitudes.modificar', $solicitud) }}" method="POST">
                @csrf
                @method('PUT')
                @include('solicitudes._form', ['adminEdit' => true])
            </form>
        </details>
    @endif

    <section class="form-editorial-panel">
        <h2 class="h5">Historial de decisiones</h2>
        @forelse($solicitud->decisiones as $decision)
            <article class="history-entry-content border-bottom py-3">
                <strong>{{ $decision->created_at->format('d/m/Y H:i') }} · {{ $decision->actor?->name ?? 'Usuario' }}</strong>
                <p class="mb-1">{{ str_replace('_', ' ', ucfirst($decision->accion)) }}</p>
                @if($decision->estado_anterior || $decision->estado_nuevo)
                    <small>{{ $decision->estado_anterior ?? '—' }} → {{ $decision->estado_nuevo }}</small>
                @endif
                @if($decision->motivo)<p class="mb-1">Motivo: {{ $decision->motivo }}</p>@endif
                @if($decision->cambios)
                    <ul class="mb-0">@foreach($decision->cambios as $campo => $cambio)<li>{{ $campo }}: {{ json_encode($cambio, JSON_UNESCAPED_UNICODE) }}</li>@endforeach</ul>
                @endif
            </article>
        @empty
            <p class="mb-0">No hay decisiones registradas.</p>
        @endforelse
    </section>
</div>
@endsection
