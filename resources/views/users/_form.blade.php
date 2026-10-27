<div class="row">
    <div class="col-md-6 form-group">
        <label for="name">Nombre *</label>
        <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $user->name) }}" required autofocus>
    </div>
    <div class="col-md-6 form-group">
        <label for="email">Correo (usuario para ingresar) *</label>
        <input type="email" class="form-control" name="email" id="email" value="{{ old('email', $user->email) }}" required>
    </div>
    <div class="col-md-6 form-group">
        <label for="password">Contraseña {{ $user->exists ? '(déjela vacía para no cambiarla)' : '*' }}</label>
        <input type="password" class="form-control" name="password" id="password" autocomplete="new-password" {{ $user->exists ? '' : 'required' }} minlength="6">
    </div>
    <div class="col-md-6 form-group">
        <label for="password_confirmation">Confirmar contraseña</label>
        <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" autocomplete="new-password">
    </div>
</div>
<label>Roles</label>
@php($selected = old('roles', $user->exists ? $user->roles->pluck('id')->all() : []))
<div class="row">
    @foreach ($roles as $role)
        <div class="col-md-4">
            <div class="form-check">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $selected))>
                    <span class="form-check-sign"><span class="check"></span></span>
                    <b>{{ $role->name }}</b>
                    @if ($role->description)<br><small class="text-muted">{{ $role->description }}</small>@endif
                </label>
            </div>
        </div>
    @endforeach
</div>
