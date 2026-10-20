
@extends('layouts.app')

@section('title', 'Calendario general')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h1>Calendario general</h1>
        <p class="text-muted">
            Consulta las actividades y los turnos programados del ISAM.
        </p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div id="calendario"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.19/index.global.min.css">
@endpush

@push('scripts')
    @vite('resources/js/calendario.js')
@endpush
