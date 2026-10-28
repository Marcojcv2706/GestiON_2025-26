@extends('layouts.app')

@section('title', 'Editar usuario — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> ADMINISTRACIÓN · {{ $usuario->name }}</p>
        <h1>Editar usuario</h1>
    </header>
    <div class="form-editorial-panel">
        <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.usuarios._form')
        </form>
    </div>
</div>
@endsection
