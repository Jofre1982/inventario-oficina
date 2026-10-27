@extends('layouts.app', ['page' => 'Mi perfil', 'pageSlug' => 'profile'])

@section('content')
@include('partials.page-header', ['title' => 'Mi perfil'])
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Datos</h4></div>
            <form method="POST" action="{{ route('profile.update') }}" autocomplete="off">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nombre</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Correo (usuario para ingresar)</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <p class="mb-0"><span class="text-muted">Roles:</span>
                        @forelse ($user->roles as $role)<span class="badge badge-info">{{ $role->name }}</span> @empty <span class="text-warning">sin rol</span> @endforelse
                    </p>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Cambiar contraseña</h4></div>
            <form method="POST" action="{{ route('profile.password') }}" autocomplete="off">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label for="old_password">Contraseña actual</label>
                        <input type="password" name="old_password" id="old_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Nueva contraseña (mínimo 6 caracteres)</label>
                        <input type="password" name="password" id="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Repita la nueva contraseña</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Cambiar contraseña</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
