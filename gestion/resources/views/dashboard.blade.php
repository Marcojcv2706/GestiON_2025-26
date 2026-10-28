@extends('layouts.app')

@section('title', 'Inicio — GestiON')

@section('content')
@php
    $isAdmin = auth()->user()->can('es-admin');
    $isTeacher = auth()->user()->can('es-profesor');
    $isStudent = auth()->user()->can('es-usuario');
    $dashboardRole = $isAdmin ? 'admin' : ($isTeacher ? 'profesor' : 'usuario');

    $phrases = match ($dashboardRole) {
        'admin' => [
            '¿Listo para organizar?',
            '¿Qué vamos a organizar hoy?',
            '¿Nuevos planes para esta semana?',
            '¿Hay algo que agregar?',
            '¿Qué tenemos para hoy?',
            '¿Listo para gestionar?',
            '¿Qué sigue?',
            '¿Hay un nuevo evento?',
            '¿Qué podemos organizar hoy?',
            '¿Tienes algo nuevo para agregar?',
        ],
        'profesor' => [
            '¿Tienes algo en mente?',
            '¿Nuevos planes para esta semana?',
            '¿Qué quieres organizar?',
            '¿Tienes una actividad en mente?',
            '¿Qué estás preparando?',
            '¿Hora de organizarlo?',
            '¿Tienes un evento en mente?',
            '¿Qué podemos organizar?',
            '¿Qué tienes pensado?',
            '¿Quieres organizar algo?',
        ],
        default => [
            '¿Qué hay esta semana?',
            '¿Qué eventos tenemos?',
            '¿Qué viene próximamente?',
            '¿Qué está pasando en ISAM?',
            '¿Ya viste los próximos eventos?',
            '¿Qué hay para hoy?',
            '¿Qué actividades vienen?',
            '¿Quieres ver qué hay?',
            '¿Qué tenemos esta semana?',
            '¿Ya viste el calendario?',
        ],
    };
@endphp

<div class="operations-dashboard">
    <section class="dashboard-action-hero" data-dashboard-role="{{ $dashboardRole }}">
        <div class="dashboard-action-copy">
            <p class="dashboard-date">{{ now()->locale('es')->translatedFormat('l, d \d\e F Y') }}</p>
            <h1 id="dashboard-prompt" data-phrases='@json($phrases)'>{{ $phrases[0] }}</h1>
            @if($isAdmin)
                <p>Organiza una actividad y coordina sus espacios y recursos.</p>
                <a class="dashboard-primary-action" href="{{ route('admin.eventos.create') }}">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    <span>Agregar evento</span>
                </a>
            @elseif($isTeacher)
                <p>Envía tu propuesta para que el equipo pueda revisarla.</p>
                <a class="dashboard-primary-action" href="{{ route('solicitudes.create') }}">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    <span>Solicitar un evento</span>
                </a>
            @elseif($isStudent)
                <p>Pide los materiales que necesitas para tus actividades.</p>
                <a class="dashboard-primary-action" href="{{ route('solicitudes-recursos.create') }}">
                    <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                    <span>Solicitar implementos</span>
                </a>
            @else
                <p>Consulta las actividades y horarios de ISAM.</p>
                <a class="dashboard-primary-action" href="{{ route('eventos.index') }}">
                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                    <span>Ver eventos</span>
                </a>
            @endif
        </div>
        <div class="dashboard-action-mark" aria-hidden="true">
            @if($isAdmin)
                <i class="fa-regular fa-calendar-plus"></i>
            @elseif($isTeacher)
                <i class="fa-regular fa-calendar-check"></i>
            @elseif($isStudent)
                <i class="fa-solid fa-box-open"></i>
            @else
                <i class="fa-regular fa-calendar-days"></i>
            @endif
        </div>
    </section>

    <div class="dashboard-content-grid dashboard-content-grid-single">
        <section class="dashboard-panel dashboard-agenda">
            <header class="dashboard-panel-header">
                <div>
                    <p class="dashboard-overline">AGENDA</p>
                    <h2>Próximos eventos</h2>
                </div>
                <a class="dashboard-panel-link" href="{{ route('eventos.index') }}">
                    Ver todos <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </header>

            @forelse($proximosEventos as $evento)
                <article class="dashboard-event-row">
                    <div class="dashboard-event-date">
                        <span>{{ $evento->start_time->locale('es')->translatedFormat('M') }}</span>
                        <strong>{{ $evento->start_time->format('d') }}</strong>
                    </div>
                    <div class="dashboard-event-details">
                        <h3>{{ $evento->name }}</h3>
                        <p>
                            <span><i class="fa-regular fa-clock" aria-hidden="true"></i>{{ $evento->start_time->format('H:i') }}–{{ $evento->end_time->format('H:i') }}</span>
                            @php($subspaceNames = $evento->subEspacios->pluck('name'))
                            @if($subspaceNames->isEmpty() && $evento->subEspacio)
                                @php($subspaceNames = collect([$evento->subEspacio->name]))
                            @endif
                            <span>
                                <i class="fa-regular fa-building" aria-hidden="true"></i>
                                {{ $evento->espacio?->name ?? 'Espacio pendiente' }}
                                @if($subspaceNames->isNotEmpty()) · {{ $subspaceNames->implode(' + ') }} @endif
                            </span>
                        </p>
                        @if($evento->recursos->isNotEmpty())
                            <div class="dashboard-event-resources">
                                @foreach($evento->recursos as $recurso)
                                    <span>{{ $recurso->name }} ×{{ $recurso->pivot->cantidad }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @can('es-admin')
                        <a class="dashboard-event-action" href="{{ route('admin.eventos.edit', $evento) }}">Editar</a>
                    @endcan
                </article>
            @empty
                <div class="dashboard-empty">
                    <span class="dashboard-empty-icon"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
                    <div>
                        <h3>No hay eventos próximos</h3>
                        <p>Las próximas actividades aparecerán aquí.</p>
                    </div>
                </div>
            @endforelse
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const dashboardPrompt = document.getElementById('dashboard-prompt');
    const dashboardPhrases = JSON.parse(dashboardPrompt.dataset.phrases);
    const promptStorageKey = `gestion.dashboard.prompt.${dashboardPrompt.closest('[data-dashboard-role]').dataset.dashboardRole}`;
    const previousPrompt = sessionStorage.getItem(promptStorageKey);
    const availablePrompts = dashboardPhrases.filter((phrase) => phrase !== previousPrompt);
    const selectedPrompt = availablePrompts[Math.floor(Math.random() * availablePrompts.length)];

    dashboardPrompt.textContent = selectedPrompt;
    sessionStorage.setItem(promptStorageKey, selectedPrompt);
</script>
@endpush
