@extends('layouts.app')

@section('title', 'Calendario — GestiON')

@section('content')
<div class="dashboard-calendar-page">
    <header class="calendar-page-heading">
        <div>
            <p class="eyebrow"><span class="eyebrow-line"></span> GESTIÓN DEL TIEMPO</p>
            <h1 class="display-5 fw-semibold">Calendario general</h1>
            <p>Consulta los eventos programados y el historial de actividades del Instituto Superior Adventista de Misiones.</p>
        </div>
    </header>

    <div class="calendar-page-panel">
        <div id="calendario" aria-label="Calendario de eventos y actividades"></div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.22/index.global.min.css">
@endpush

@push('scripts')
    @vite('resources/js/calendario.js')
@endpush
