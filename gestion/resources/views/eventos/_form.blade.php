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
    <label for="name" class="form-label">Nombre del evento <span>*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
           value="{{ old('name', $evento->name ?? '') }}" maxlength="255" placeholder="Ej.: Reunión de equipo" autocomplete="off" required autofocus>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label for="description" class="form-label">Descripción <span class="form-optional">(opcional)</span></label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
              rows="3" maxlength="255">{{ old('description', $evento->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<fieldset class="form-section-block">
    <legend>Fecha y horario</legend>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="start_time" class="form-label">Inicio <span>*</span></label>
            <input type="datetime-local" class="form-control @error('start_time') is-invalid @enderror"
                   id="start_time" name="start_time"
                   value="{{ old('start_time', isset($evento) ? $evento->start_time->format('Y-m-d\TH:i') : '') }}" required>
            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="end_time" class="form-label">Fin <span>*</span></label>
            <input type="datetime-local" class="form-control @error('end_time') is-invalid @enderror"
                   id="end_time" name="end_time"
                   value="{{ old('end_time', isset($evento) ? $evento->end_time->format('Y-m-d\TH:i') : '') }}" required>
            @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</fieldset>

<fieldset class="form-section-block">
    <legend>Ubicación</legend>
    <div class="mb-3">
        <label for="espacio_id" class="form-label">Espacio <span>*</span></label>
        <select class="form-select @error('espacio_id') is-invalid @enderror" id="espacio_id" name="espacio_id" required>
            <option value="">Selecciona un espacio</option>
            @foreach($espacios as $espacio)
                <option value="{{ $espacio->id }}"
                    @selected(old('espacio_id', $evento->espacio_id ?? '') == $espacio->id)>
                    {{ $espacio->name }}
                </option>
            @endforeach
        </select>
        @error('espacio_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @php($selectedSubspaceIds = $evento?->subEspacios->modelKeys() ?? [])
    <x-gestion.subspace-selector :sub-espacios="$subEspacios" :selected-ids="$selectedSubspaceIds" />
</fieldset>

<fieldset class="form-section-block">
    <legend>Recursos <span class="form-optional">(opcional)</span></legend>
    <p class="form-section-help">Indica cuántas unidades necesitas. La reserva se descuenta del inventario hasta finalizar o cancelar el evento.</p>
    @error('recursos')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

    @if($recursos->isEmpty())
        <p class="form-empty-note">No hay recursos registrados.</p>
    @else
        <div class="resource-request-list">
            @foreach($recursos as $recurso)
                @php($reservation = isset($evento) ? $evento->recursos->firstWhere('id', $recurso->id) : null)
                @php($oldReservation = collect(old('recursos', []))->firstWhere('id', $recurso->id))
                @php($quantity = $oldReservation['cantidad'] ?? ($reservation?->pivot->cantidad ?? 0))
                @php($available = (int) $recurso->cantidad_disponible + (int) ($reservation?->pivot->cantidad ?? 0))
                <div class="resource-request-row">
                    <div class="resource-request-name">
                        <strong>{{ $recurso->name }}</strong>
                        <span>{{ $recurso->tipo }} · {{ $available }} disponibles</span>
                    </div>
                    <label class="resource-quantity-label" for="recurso-cantidad-{{ $recurso->id }}">Cantidad</label>
                    <input type="hidden" name="recursos[{{ $loop->index }}][id]" value="{{ $recurso->id }}">
                    <input class="form-control resource-quantity @error('recursos.'.$loop->index.'.cantidad') is-invalid @enderror"
                           type="number" id="recurso-cantidad-{{ $recurso->id }}"
                           name="recursos[{{ $loop->index }}][cantidad]" min="0" max="{{ $available }}"
                           value="{{ $quantity }}" data-max="{{ $available }}" aria-label="Cantidad de {{ $recurso->name }}" required>
                    @error('recursos.'.$loop->index.'.cantidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
    @endif
</fieldset>

<div class="form-actions">
    <a href="{{ route('eventos.index') }}" class="action-link action-link-cancel">Cancelar</a>
    <button type="submit" class="action-link action-link-primary">
        {{ isset($evento) ? 'Guardar cambios' : 'Crear evento' }}
    </button>
</div>
