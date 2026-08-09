@extends('layouts.app')

@section('title', 'Editar Actividad')

@section('content')

<div class="container-fluid">

    <h1 class="mt-4 mb-4">
        Editar Actividad
    </h1>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <form action="{{ route('actividades.update', $actividad) }}" method="POST">
                @csrf
                @method('PUT')

                @include('actividades._form')
            </form>

        </div>
    </div>

</div>

@endsection