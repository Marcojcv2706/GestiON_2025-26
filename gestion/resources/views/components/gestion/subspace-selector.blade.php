@props(['subEspacios', 'selectedIds' => []])

@php($selectedIds = array_map('strval', old('sub_espacios', $selectedIds)))

<div data-subspace-picker role="group" aria-label="Subespacios del espacio seleccionado">
    <label class="form-label">Subespacios <span class="form-optional">(si aplica)</span></label>
    <div class="subspace-options" data-subspace-options>
        @foreach($subEspacios as $subEspacio)
            <label class="subspace-option" data-space-id="{{ $subEspacio->espacio_id }}">
                <input type="checkbox" name="sub_espacios[]" value="{{ $subEspacio->id }}"
                       @checked(in_array((string) $subEspacio->id, $selectedIds, true))>
                <span>{{ $subEspacio->name }}</span>
            </label>
        @endforeach
    </div>
    <p class="form-text mb-0" data-subspace-placeholder>Selecciona primero un espacio.</p>
    <p class="form-text mb-0" data-subspace-not-applicable hidden>No aplica: este espacio no tiene subespacios.</p>
    @error('sub_espacios')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
</div>
