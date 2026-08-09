@if ($errors->any())
    <div class="alert alert-danger shadow-sm border-0">
        <strong>Se encontraron algunos errores:</strong>

        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


{{-- NOMBRE --}}
<div class="mb-4">

    <label for="name" class="form-label fw-semibold">
        Nombre de la Actividad
    </label>

    <input
        type="text"
        class="form-control transition-all duration-200"
        id="name"
        name="name"
        value="{{ old('name', $actividad->name ?? '') }}"
        placeholder="Ingrese el nombre de la actividad"
        required
    >

</div>


{{-- SUB-ESPACIO --}}
<div class="mb-4">

    <label for="sub_espacio_id" class="form-label fw-semibold">
        Sub-espacio
    </label>

    <select
        class="form-select transition-all duration-200"
        id="sub_espacio_id"
        name="sub_espacio_id"
        required
    >

        <option value="">
            Seleccione un sub-espacio...
        </option>

        @foreach($subEspacios as $subEspacio)

            <option
                value="{{ $subEspacio->id }}"
                @selected(old('sub_espacio_id', $actividad->sub_espacio_id ?? '') == $subEspacio->id)
            >
                {{ $subEspacio->name }}
            </option>

        @endforeach

    </select>

</div>


{{-- DESCRIPCIÓN --}}
<div class="mb-4">

    <label for="description" class="form-label fw-semibold">
        Descripción
        <span class="text-muted fw-normal">(Opcional)</span>
    </label>

    <textarea
        class="form-control transition-all duration-200"
        id="description"
        name="description"
        rows="4"
        placeholder="Ingrese una descripción de la actividad..."
    >{{ old('description', $actividad->description ?? '') }}</textarea>

</div>


{{-- BOTONES --}}
<div class="d-flex gap-2 mt-4">

    {{-- CANCELAR --}}
    <a
        href="{{ route('actividades.index') }}"
        class="btn btn-secondary transition-all duration-200 ease-out hover:scale-105 hover:shadow active:scale-95"
    >
        Cancelar
    </a>


    {{-- GUARDAR --}}
    <button
        type="submit"
        class="btn btn-primary transition-all duration-200 ease-out hover:scale-105 hover:shadow-lg active:scale-95"
    >
        {{ isset($actividad) ? 'Actualizar Actividad' : 'Crear Actividad' }}
    </button>

</div>