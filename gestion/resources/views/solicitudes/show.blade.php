@extends('layouts.app')

@section('title', 'Solicitud #'.$solicitud->id.' — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> SOLICITUD DE EVENTO #{{ $solicitud->id }}</p>
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
    @php($adminModifications = $solicitud->decisiones->where('accion', 'solicitud_modificada_por_admin'))
    @if($solicitud->estado === \App\Models\SolicitudEvento::APROBADA)
        <div class="alert alert-success request-status-message" role="status">
            <strong>{{ $isAdmin ? 'La solicitud de evento fue aprobada.' : 'Tu solicitud de evento fue aprobada.' }}</strong>
            @if($adminModifications->isNotEmpty())
                <span>El administrador también realizó modificaciones; consulta el historial para ver cuáles.</span>
            @endif
            @if($solicitud->revisor)
                <span>Aprobada por {{ $solicitud->revisor->name }}{{ $solicitud->decidido_en ? ' el '.$solicitud->decidido_en->format('d/m/Y H:i') : '' }}.</span>
            @endif
        </div>
    @elseif($solicitud->estado === \App\Models\SolicitudEvento::RECHAZADA)
        <div class="alert alert-danger request-status-message" role="status">
            <strong>{{ $isAdmin ? 'La solicitud de evento fue rechazada.' : 'Tu solicitud de evento fue rechazada.' }}</strong>
            @if($solicitud->observacion_admin)<span>Motivo: {{ $solicitud->observacion_admin }}</span>@endif
            @if($solicitud->revisor)
                <span>Revisada por {{ $solicitud->revisor->name }}{{ $solicitud->decidido_en ? ' el '.$solicitud->decidido_en->format('d/m/Y H:i') : '' }}.</span>
            @endif
        </div>
    @elseif($solicitud->estado === \App\Models\SolicitudEvento::MODIFICACION_SOLICITADA)
        <div class="alert alert-warning request-status-message" role="status">
            Esta solicitud conserva un estado de revisión anterior. El administrador puede editarla directamente y continuar con la aprobación o el rechazo.
        </div>
    @endif

    @if($adminModifications->isNotEmpty())
        <div class="alert alert-info request-status-message" role="status">
            <strong>El administrador modificó esta solicitud.</strong>
            @foreach($adminModifications as $modification)
                @if($modification->actor)
                    <span>Modificada por {{ $modification->actor->name }} el {{ $modification->created_at->format('d/m/Y H:i') }}.</span>
                @endif
                @if($modification->motivo)<span>Nota: {{ $modification->motivo }}</span>@endif
            @endforeach
        </div>
    @endif

    <section class="form-editorial-panel mb-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Solicitante</dt><dd class="col-sm-9">{{ $solicitud->user->name }}</dd>
            <dt class="col-sm-3">Fecha de solicitud</dt><dd class="col-sm-9">{{ $solicitud->created_at->locale('es')->translatedFormat('d F Y, H:i') }}</dd>
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

    @if($solicitud->evento)
        <p><a class="action-link action-link-secondary" href="{{ route('eventos.index') }}">Ver evento aprobado</a></p>
    @endif

    @if($isAdmin && in_array($solicitud->estado, [\App\Models\SolicitudEvento::PENDIENTE, \App\Models\SolicitudEvento::MODIFICACION_SOLICITADA], true))
        <section class="form-editorial-panel mb-4">
            <h2 class="h5">Decisión administrativa</h2>
            <div class="form-actions">
                <form action="{{ route('admin.solicitudes.aprobar', $solicitud) }}" method="POST"
                      onsubmit="return confirm('¿Confirmas que quieres aprobar esta solicitud de evento?');">
                    @csrf
                    <button class="action-link action-link-primary" type="submit">Aprobar</button>
                </form>
            </div>
            <div class="row g-3 mt-2">
                <form class="col-md-6" action="{{ route('admin.solicitudes.rechazar', $solicitud) }}" method="POST"
                      onsubmit="return confirm('¿Confirmas que quieres rechazar esta solicitud?');">
                    @csrf
                    <label class="form-label" for="motivo-rechazo">Motivo del rechazo *</label>
                    <textarea class="form-control" id="motivo-rechazo" name="motivo" rows="3" required minlength="5"></textarea>
                    <button class="action-link action-link-danger mt-2" type="submit">Rechazar solicitud</button>
                </form>
            </div>
        </section>

        <details class="form-editorial-panel mb-4">
            <summary>Editar solicitud como administrador</summary>
            <form class="mt-4" action="{{ route('admin.solicitudes.modificar', $solicitud) }}" method="POST">
                @csrf
                @method('PUT')
                @include('solicitudes._form', ['adminEdit' => true])
            </form>
        </details>
    @endif

    <section class="form-editorial-panel">
        <h2 class="h5">Historial de decisiones</h2>
        @php($changeLabels = ['name' => 'Nombre', 'description' => 'Descripción', 'start_time' => 'Inicio', 'end_time' => 'Finalización', 'espacio_id' => 'Espacio', 'sub_espacios' => 'Subespacios', 'recursos' => 'Implementos'])
        @forelse($solicitud->decisiones as $decision)
            <article class="history-entry-content border-bottom py-3">
                <strong>{{ $decision->created_at->format('d/m/Y H:i') }} · {{ $decision->actor?->name ?? 'Usuario' }}</strong>
                <p class="mb-1">{{ str_replace('_', ' ', ucfirst($decision->accion)) }}</p>
                @if($decision->estado_anterior || $decision->estado_nuevo)
                    <small>{{ $decision->estado_anterior ?? '—' }} → {{ $decision->estado_nuevo }}</small>
                @endif
                @if($decision->motivo)<p class="mb-1">Motivo: {{ $decision->motivo }}</p>@endif
                @if($decision->cambios)
                    <ul class="mb-0">@foreach($decision->cambios as $campo => $cambio)<li>{{ $changeLabels[$campo] ?? $campo }}: {{ json_encode($cambio, JSON_UNESCAPED_UNICODE) }}</li>@endforeach</ul>
                @endif
            </article>
        @empty
            <p class="mb-0">No hay decisiones registradas.</p>
        @endforelse
    </section>
</div>
@endsection
