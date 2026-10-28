@extends('layouts.app')

@section('title', 'Crear recurso — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> INVENTARIO</p>
        <h1>Crear recurso</h1>
    </header>

    <div class="form-editorial-panel">
        <form action="{{ route('admin.recursos.store') }}" method="POST">
            @csrf
            @include('admin.recursos._form')
        </form>
    </div>
</div>
@endsection
