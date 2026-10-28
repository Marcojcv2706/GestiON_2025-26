@extends('layouts.app')

@section('title', 'Nuevo evento — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> ADMINISTRACIÓN</p>
        <h1>Crear evento</h1>
        <p>Registra un evento institucional. Para solicitar aprobación, usa Mis solicitudes.</p>
    </header>

    <div class="form-editorial-panel">
        <form action="{{ route('admin.eventos.store') }}" method="POST">
            @csrf
            @include('eventos._form')
        </form>
    </div>
</div>
@endsection
