@extends('layouts.app')

@section('title', 'Editar recurso — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> INVENTARIO · {{ $recurso->name }}</p>
        <h1>Editar recurso</h1>
    </header>

    <div class="form-editorial-panel">
        <form action="{{ route('admin.recursos.update', $recurso) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.recursos._form')
        </form>
    </div>
</div>
@endsection
