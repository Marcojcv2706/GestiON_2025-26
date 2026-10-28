@extends('layouts.app')

@section('title', 'Editar evento — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> EVENTOS</p>
        <h1>Editar evento</h1>
    </header>

    <div class="form-editorial-panel">
        <form action="{{ route('admin.eventos.update', $evento) }}" method="POST">
            @csrf
            @method('PUT')
            @include('eventos._form')
        </form>
    </div>
</div>
@endsection
