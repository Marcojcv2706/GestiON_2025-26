@extends('layouts.app')

@section('title', 'Solicitar implementos — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> IMPLEMENTOS</p>
        <h1>Solicitar implementos</h1>
        <p>Elige lo que necesitas. La solicitud debe ser aprobada antes de retirar los elementos.</p>
    </header>

    <form class="form-editorial-panel resource-request-form" action="{{ route('solicitudes-recursos.store') }}" method="POST">
        @csrf
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>No pudimos enviar la solicitud.</strong>
                <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="mb-4">
            <label class="form-label" for="fecha_uso">Fecha en que necesitas los implementos</label>
            <input class="form-control @error('fecha_uso') is-invalid @enderror" type="date" id="fecha_uso" name="fecha_uso"
                   value="{{ old('fecha_uso') }}" min="{{ now()->toDateString() }}" required>
            @error('fecha_uso')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <fieldset class="resource-request-fieldset">
            <legend>Implementos y cantidades</legend>
            <p class="form-text">Escribe cuántas unidades necesitas. Ingresa 0 si no necesitas ese elemento.</p>
            @error('recursos')<p class="text-danger fw-semibold" role="alert">{{ $message }}</p>@enderror

            @forelse($recursos as $recurso)
                <div class="resource-request-choice">
                    <div>
                        <label for="recurso-{{ $recurso->id }}">{{ $recurso->name }}</label>
                        <span>{{ $recurso->tipo }} · {{ $recurso->cantidad_disponible }} disponibles</span>
                    </div>
                    <label class="resource-request-quantity">
                        <span class="visually-hidden">Cantidad de {{ $recurso->name }}</span>
                        <input class="form-control" type="number" id="recurso-{{ $recurso->id }}"
                               name="recursos[{{ $recurso->id }}]" min="0" max="{{ $recurso->cantidad_disponible }}"
                               value="{{ old('recursos.'.$recurso->id, 0) }}" inputmode="numeric">
                    </label>
                </div>
            @empty
                <p class="form-empty-note">No hay implementos registrados para solicitar.</p>
            @endforelse
        </fieldset>

        <div class="mt-4">
            <label class="form-label" for="motivo">¿Para qué los necesitas?</label>
            <textarea class="form-control @error('motivo') is-invalid @enderror" id="motivo" name="motivo" rows="3"
                      maxlength="2000" required>{{ old('motivo') }}</textarea>
            @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="resource-request-note" role="note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>Los implementos se descuentan del inventario al aprobarse y vuelven a estar disponibles cuando se registra su devolución.</span>
        </div>

        <div class="form-actions">
            <a class="action-link action-link-cancel" href="{{ route('solicitudes-recursos.index') }}">Cancelar</a>
            <button class="action-link action-link-primary" type="submit" @disabled($recursos->isEmpty())>Enviar solicitud</button>
        </div>
    </form>
</div>
@endsection
