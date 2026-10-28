@if ($errors->any())
    <div class="alert alert-danger form-error-summary" role="alert">
        <h2>Revisa estos campos antes de continuar</h2>
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="mb-4">
    <label for="name" class="form-label">Nombre del evento <span>*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
           value="{{ old('name', $solicitud?->name ?? '') }}" maxlength="255" placeholder="Ej.: Reunión de familias" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label for="description" class="form-label">Descripción <span class="form-optional">(opcional)</span></label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
              rows="3" maxlength="2000">{{ old('description', $solicitud?->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<fieldset class="form-section-block">
    <legend>1. ¿Dónde será?</legend>
    <div class="mb-3">
        <label for="espacio_id" class="form-label">Espacio <span>*</span></label>
        <select class="form-select @error('espacio_id') is-invalid @enderror" id="espacio_id" name="espacio_id" required>
            <option value="">Selecciona un espacio</option>
            @foreach($espacios as $espacio)
                <option value="{{ $espacio->id }}" @selected(old('espacio_id', $solicitud?->espacio_id ?? '') == $espacio->id)>
                    {{ $espacio->name }}
                </option>
            @endforeach
        </select>
        @error('espacio_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @php($selectedSubspaceIds = $solicitud?->subEspacios->modelKeys() ?? [])
    <x-gestion.subspace-selector :sub-espacios="$subEspacios" :selected-ids="$selectedSubspaceIds" />
</fieldset>

<fieldset class="form-section-block">
    <legend>2. ¿Cuándo será?</legend>
    <p class="form-section-help">Indica la fecha y la hora de inicio y finalización.</p>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="start_time" class="form-label">Fecha y hora de inicio <span>*</span></label>
            <input type="datetime-local" class="form-control @error('start_time') is-invalid @enderror"
                   id="start_time" name="start_time"
                   value="{{ old('start_time', $solicitud?->start_time?->format('Y-m-d\TH:i') ?? '') }}" required>
            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="end_time" class="form-label">Fecha y hora de finalización <span>*</span></label>
            <input type="datetime-local" class="form-control @error('end_time') is-invalid @enderror"
                   id="end_time" name="end_time"
                   value="{{ old('end_time', $solicitud?->end_time?->format('Y-m-d\TH:i') ?? '') }}" required>
            @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</fieldset>

<fieldset class="form-section-block">
    <legend>3. ¿Qué recursos necesitas? <span class="form-optional">(opcional)</span></legend>
    <p class="form-section-help">Escribe 0 si no necesitas un recurso. La disponibilidad se confirma al revisar la solicitud.</p>
    @error('recursos')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
    <div class="resource-request-list">
        @foreach($recursos as $recurso)
            @php($requested = $solicitud?->recursos->firstWhere('id', $recurso->id))
            @php($oldRequested = collect(old('recursos', []))->firstWhere('id', $recurso->id))
            @php($quantity = $oldRequested['cantidad'] ?? ($requested?->pivot->cantidad ?? 0))
            <div class="resource-request-row">
                <div class="resource-request-name">
                    <strong>{{ $recurso->name }}</strong>
                    <span>{{ $recurso->cantidad_disponible }} disponibles · {{ $recurso->cantidad_total }} en total</span>
                </div>
                <label class="resource-quantity-label" for="solicitud-recurso-{{ $recurso->id }}">Cantidad</label>
                <input type="hidden" name="recursos[{{ $loop->index }}][id]" value="{{ $recurso->id }}">
                <input class="form-control" type="number" id="solicitud-recurso-{{ $recurso->id }}"
                       name="recursos[{{ $loop->index }}][cantidad]" min="0" max="{{ $recurso->cantidad_total }}"
                       value="{{ $quantity }}" required>
            </div>
        @endforeach
    </div>
</fieldset>

@if($adminEdit ?? false)
    <div class="mb-4">
        <label for="motivo" class="form-label">Motivo del cambio <span>*</span></label>
        <textarea class="form-control @error('motivo') is-invalid @enderror" id="motivo" name="motivo" rows="3" required>{{ old('motivo') }}</textarea>
        @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
@endif

<div class="form-actions">
    <a href="{{ route('solicitudes.index') }}" class="action-link action-link-cancel">Cancelar</a>
    <button type="submit" class="action-link action-link-primary request-submit-button">
        {{ $adminEdit ?? false ? 'Guardar cambios' : ($solicitud ? 'Guardar cambios' : 'Enviar solicitud') }}
    </button>
</div>
