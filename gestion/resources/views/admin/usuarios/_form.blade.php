@if ($errors->any())
    <div class="alert alert-danger form-error-summary" role="alert">
        <h2>Revisa estos campos antes de continuar</h2>
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<p class="form-required-note"><span>*</span> Campo obligatorio</p>

<div class="mb-4">
    <label for="name" class="form-label">Nombre completo <span>*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
           value="{{ old('name', $usuario->name ?? '') }}" autocomplete="name" required autofocus>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label for="email" class="form-label">Correo electrónico <span>*</span></label>
    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
           value="{{ old('email', $usuario->email ?? '') }}" autocomplete="email" required>
    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label for="role_id" class="form-label">Rol <span>*</span></label>
    <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
        <option value="">Selecciona un rol</option>
        @foreach($roles as $rol)
            <option value="{{ $rol->id }}" @selected(old('role_id', $usuario->role_id ?? '') == $rol->id)>
                {{ $rol->name }}
            </option>
        @endforeach
    </select>
    @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<fieldset class="form-section-block">
    <legend>{{ isset($usuario) ? 'Cambiar contraseña' : 'Contraseña' }}</legend>
    @if(isset($usuario))
        <p class="form-section-help">Deja estos campos vacíos para conservar la contraseña actual.</p>
    @endif
    <div class="mb-4">
        <label for="password" class="form-label">Contraseña {{ isset($usuario) ? '' : '*' }}</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
               autocomplete="new-password" {{ isset($usuario) ? '' : 'required' }}>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-2">
        <label for="password_confirmation" class="form-label">Confirmar contraseña {{ isset($usuario) ? '' : '*' }}</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
               autocomplete="new-password" {{ isset($usuario) ? '' : 'required' }}>
    </div>
</fieldset>

<div class="form-actions">
    <a href="{{ route('admin.usuarios.index') }}" class="action-link action-link-cancel">Cancelar</a>
    <button type="submit" class="action-link action-link-primary">
        {{ isset($usuario) ? 'Guardar cambios' : 'Crear usuario' }}
    </button>
</div>
