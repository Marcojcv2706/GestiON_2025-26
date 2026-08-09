@extends('layouts.app')

@section('title', 'Crear Actividad')

@section('content')

<div class="container-fluid">

    <h1 class="mt-4 mb-4">
        Crear Nueva Actividad
    </h1>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <form action="{{ route('actividades.store') }}" method="POST">
                @csrf

                @include('actividades._form')
            </form>

        </div>
    </div>

</div>

@endsection