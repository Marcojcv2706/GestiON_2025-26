@extends('layouts.app')

@section('title', 'Crear usuario — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> ADMINISTRACIÓN</p>
        <h1>Crear usuario</h1>
    </header>
    <div class="form-editorial-panel">
        <form action="{{ route('admin.usuarios.store') }}" method="POST">
            @csrf
            @include('admin.usuarios._form')
        </form>
    </div>
</div>
@endsection
