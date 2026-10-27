@extends('layouts.app', ['page' => 'Usuarios', 'pageSlug' => 'users'])

@section('content')
@include('partials.page-header', ['title' => 'Nuevo usuario'])
<div class="card">
    <form method="POST" action="{{ route('users.store') }}" autocomplete="off">
    @csrf

    <div class="card-body">@include('users._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Registrar</button>
        <a href="{{ route('users.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
