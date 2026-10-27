@extends('layouts.app', ['class' => 'login-page', 'page' => 'Ingresar', 'contentClass' => 'login-page'])

@section('content')
@php($organization = \App\Models\Organization::current())
<div class="login-brand">
    @if ($organization->logo_url)
        <img src="{{ $organization->logo_url }}" alt="Logo"><br>
    @endif
    <h3>{{ $organization->name }}</h3>
    <p class="text-muted mb-0">Control de elementos del almacén</p>
</div>
<div class="col-lg-4 col-md-6 ml-auto mr-auto">
    <form class="form" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="card card-login card-white">
            <div class="card-body pt-4">
                <p class="text-dark mb-3">Ingrese con su correo y contraseña</p>
                <div class="input-group{{ $errors->has('email') ? ' has-danger' : '' }}">
                    <div class="input-group-prepend"><div class="input-group-text"><i class="tim-icons icon-email-85"></i></div></div>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" placeholder="Correo" required autofocus>
                </div>
                <div class="input-group">
                    <div class="input-group-prepend"><div class="input-group-text"><i class="tim-icons icon-lock-circle"></i></div></div>
                    <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                </div>
                <div class="form-check mt-2">
                    <label class="form-check-label text-dark">
                        <input class="form-check-input" type="checkbox" name="remember" value="1">
                        <span class="form-check-sign"><span class="check"></span></span>
                        Recordarme en este computador
                    </label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-lg btn-block mb-3">Ingresar</button>
                <p class="text-muted small mb-0">¿Olvidó su contraseña? Pídale al administrador del sistema que le asigne una nueva.</p>
            </div>
        </div>
    </form>
</div>
@endsection
