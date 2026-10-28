@if ($errors->any())
    <div class="alert alert-danger form-error-summary" role="alert">
        <h2>Revisa estos campos antes de continuar</h2>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<p class="form-required-note"><span>*</span> Campo obligatorio</p>

<div class="mb-4">
    <label for="name" class="form-label">Nombre del implemento <span>*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
           value="{{ old('name', $recurso->name ?? '') }}" maxlength="255" required autofocus>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label for="tipo" class="form-label">Categoría <span>*</span></label>
    <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required>
        <option value="">Selecciona un tipo</option>
        @foreach($tipos as $tipo)
            <option value="{{ $tipo }}" @selected(old('tipo', $recurso->tipo ?? '') === $tipo)>{{ $tipo }}</option>
        @endforeach
    </select>
    @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label for="cantidad_total" class="form-label">Cantidad total <span>*</span></label>
    <input type="number" class="form-control @error('cantidad_total') is-invalid @enderror"
           id="cantidad_total" name="cantidad_total" min="{{ isset($recurso) ? $recurso->cantidadEnUso : 1 }}"
           step="1" value="{{ old('cantidad_total', $recurso->cantidad_total ?? 1) }}" required>
    <div class="form-text">
        @if(isset($recurso))
            Unidades reservadas: <strong>{{ $recurso->cantidadEnUso }}</strong>. No se puede establecer un total menor.
        @else
            Se crearán todas las unidades como disponibles.
        @endif
    </div>
    @error('cantidad_total')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label for="descripcion" class="form-label">Descripción <span class="form-optional">(opcional)</span></label>
    <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion"
              rows="3" maxlength="1000">{{ old('descripcion', $recurso->descripcion ?? '') }}</textarea>
    @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-inventory-note">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    <span>El estado y la cantidad disponible se calculan automáticamente según las reservas activas.</span>
</div>

<div class="form-actions">
    <a href="{{ route('admin.recursos.index') }}" class="action-link action-link-cancel">Cancelar</a>
    <button type="submit" class="action-link action-link-primary">
        {{ isset($recurso) ? 'Guardar cambios' : 'Crear recurso' }}
    </button>
</div>
