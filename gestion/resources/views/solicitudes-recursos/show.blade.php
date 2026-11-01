@extends('layouts.app')

@section('title', 'Solicitud de implementos #'.$solicitud->id.' — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> SOLICITUD DE IMPLEMENTOS #{{ $solicitud->id }}</p>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1>Detalle de solicitud</h1>
                <p>Uso previsto: {{ $solicitud->fecha_uso->locale('es')->translatedFormat('l, d F Y') }}</p>
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
        </div>
    </header>

    @include('partials._session-status')

    @if($solicitud->estado === \App\Models\SolicitudRecurso::APROBADA)
        <div class="alert alert-success request-status-message" role="status">
            <strong>{{ $isOwner ? 'Tu solicitud de implementos fue aprobada.' : 'La solicitud de implementos fue aprobada.' }}</strong>
            @if($solicitud->revisor)
                <span>Aprobada por {{ $solicitud->revisor->name }}{{ $solicitud->decidido_en ? ' el '.$solicitud->decidido_en->format('d/m/Y H:i') : '' }}.</span>
            @endif
        </div>
    @elseif($solicitud->estado === \App\Models\SolicitudRecurso::RECHAZADA)
        <div class="alert alert-danger request-status-message" role="status">
            <strong>{{ $isOwner ? 'Tu solicitud de implementos fue rechazada.' : 'La solicitud de implementos fue rechazada.' }}</strong>
            @if($solicitud->observacion_admin)<span>Motivo: {{ $solicitud->observacion_admin }}</span>@endif
            @if($solicitud->revisor)
                <span>Rechazada por {{ $solicitud->revisor->name }}{{ $solicitud->decidido_en ? ' el '.$solicitud->decidido_en->format('d/m/Y H:i') : '' }}.</span>
            @endif
        </div>
    @elseif($solicitud->estado === \App\Models\SolicitudRecurso::DEVUELTA)
        <div class="alert alert-info request-status-message" role="status">
            Los implementos fueron devueltos{{ $solicitud->devuelto_en ? ' el '.$solicitud->devuelto_en->format('d/m/Y H:i') : '' }}.
        </div>
    @endif

    <section class="form-editorial-panel mb-4">
        <dl class="row mb-0">
            <dt class="col-sm-3">Solicitante</dt><dd class="col-sm-9">{{ $solicitud->user->name }}</dd>
            <dt class="col-sm-3">Fecha de solicitud</dt><dd class="col-sm-9">{{ $solicitud->created_at->locale('es')->translatedFormat('d F Y, H:i') }}</dd>
            <dt class="col-sm-3">Motivo</dt><dd class="col-sm-9">{{ $solicitud->motivo }}</dd>
            <dt class="col-sm-3">Implementos</dt>
            <dd class="col-sm-9">
                <ul class="mb-0">
                    @foreach($solicitud->recursos as $recurso)
                        <li>{{ $recurso->name }} · {{ $recurso->pivot->cantidad }} unidades</li>
                    @endforeach
                </ul>
            </dd>
            @if($solicitud->revisor)
                <dt class="col-sm-3">Revisada por</dt><dd class="col-sm-9">{{ $solicitud->revisor->name }}</dd>
            @endif
            @if($solicitud->observacion_admin)
                <dt class="col-sm-3">Motivo de rechazo</dt><dd class="col-sm-9">{{ $solicitud->observacion_admin }}</dd>
            @endif
        </dl>
    </section>

    @if($canReview && $solicitud->estado === \App\Models\SolicitudRecurso::PENDIENTE)
        <section class="form-editorial-panel mb-4">
            <h2 class="h5">Revisar solicitud</h2>
            <div class="form-actions">
                <form action="{{ route('admin.solicitudes-recursos.aprobar', $solicitud) }}" method="POST"
                      onsubmit="return confirm('¿Confirmas que quieres aprobar esta solicitud y reservar los implementos?');">
                    @csrf
                    <button class="action-link action-link-primary" type="submit">Aprobar y reservar</button>
                </form>
            </div>
            <form class="resource-request-reject mt-3" action="{{ route('admin.solicitudes-recursos.rechazar', $solicitud) }}" method="POST"
                  onsubmit="return confirm('¿Confirmas que quieres rechazar esta solicitud?');">
                @csrf
                <label class="form-label" for="motivo-rechazo">Motivo del rechazo *</label>
                <textarea class="form-control" id="motivo-rechazo" name="observacion_admin" rows="3"
                          maxlength="2000" required>{{ old('observacion_admin') }}</textarea>
                @error('observacion_admin')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                <button class="action-link action-link-danger mt-2" type="submit">Rechazar solicitud</button>
            </form>
        </section>
    @elseif($isAdmin && $solicitud->estado === \App\Models\SolicitudRecurso::APROBADA)
        <section class="form-editorial-panel mb-4">
            <h2 class="h5">Registrar devolución</h2>
            <form action="{{ route('admin.solicitudes-recursos.devolver', $solicitud) }}" method="POST"
                  onsubmit="return confirm('¿Confirmas que recibiste los implementos devueltos?');">
                @csrf
                <button class="action-link action-link-primary" type="submit">Registrar devolución</button>
            </form>
        </section>
    @endif

    <a class="action-link action-link-secondary" href="{{ $isOwner ? route('solicitudes-recursos.index') : route('solicitudes.index', ['tipo' => 'implementos']) }}">Volver a solicitudes</a>
</div>
@endsection
