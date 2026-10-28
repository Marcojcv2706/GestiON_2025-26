@extends('layouts.app')

@section('title', 'Editar espacio — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> ADMINISTRACIÓN · {{ $espacio->name }}</p>
        <h1>Editar espacio</h1>
    </header>
    <div class="form-editorial-panel">
        <form action="{{ route('admin.espacios.update', $espacio) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.espacios._form')
        </form>
    </div>
</div>
@endsection
