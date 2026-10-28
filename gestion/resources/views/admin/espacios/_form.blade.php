@if ($errors->any())
    <div class="alert alert-danger form-error-summary" role="alert">
        <h2>Revisa estos campos antes de continuar</h2>
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<p class="form-required-note"><span>*</span> Campo obligatorio</p>
<div class="mb-4">
    <label for="name" class="form-label">Nombre del espacio <span>*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
           value="{{ old('name', $espacio->name ?? '') }}" placeholder="Ej.: Gimnasio principal" maxlength="255" required autofocus>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-actions">
    <a href="{{ route('admin.espacios.index') }}" class="action-link action-link-cancel">Cancelar</a>
    <button type="submit" class="action-link action-link-primary">
        {{ isset($espacio) ? 'Guardar cambios' : 'Crear espacio' }}
    </button>
</div>