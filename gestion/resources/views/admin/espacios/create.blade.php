@extends('layouts.app')

@section('title', 'Crear espacio — GestiON')

@section('content')
<div class="editorial-page">
    <header class="editorial-page-heading">
        <p class="eyebrow"><span class="eyebrow-line"></span> ADMINISTRACIÓN</p>
        <h1>Crear espacio</h1>
    </header>
    <div class="form-editorial-panel">
        <form action="{{ route('admin.espacios.store') }}" method="POST">
            @csrf
            @include('admin.espacios._form')
        </form>
    </div>
</div>
@endsection
