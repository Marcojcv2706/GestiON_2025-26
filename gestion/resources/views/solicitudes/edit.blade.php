@extends('layouts.app')

@section('title', 'Modificar solicitud — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> SOLICITUD #{{ $solicitud->id }}</p>
        <h1>Modificar solicitud</h1>
    </header>
    @if($solicitud->observacion_admin)
        <div class="alert alert-warning request-status-message" role="status">
            <strong>Motivo de los cambios solicitados:</strong>
            <span>{{ $solicitud->observacion_admin }}</span>
            <span>Actualiza los datos y guárdalos para volver a enviarlos.</span>
        </div>
    @endif
    <div class="form-editorial-panel">
        <form action="{{ route('solicitudes.update', $solicitud) }}" method="POST">
            @csrf
            @method('PUT')
            @include('solicitudes._form')
        </form>
    </div>
</div>
@endsection
