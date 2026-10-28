@extends('layouts.app')

@section('title', 'Nueva solicitud — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> SOLICITUDES</p>
        <h1>Solicitar evento</h1>
        <p>Completa los datos y envía la solicitud para que el equipo pueda revisarla.</p>
    </header>
    <div class="form-editorial-panel">
        <form action="{{ route('solicitudes.store') }}" method="POST">
            @csrf
            @include('solicitudes._form', ['solicitud' => null])
        </form>
    </div>
</div>
@endsection
