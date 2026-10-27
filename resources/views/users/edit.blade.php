@extends('layouts.app', ['page' => 'Usuarios', 'pageSlug' => 'users'])

@section('content')
@include('partials.page-header', ['title' => 'Editar usuario'])
<div class="card">
    <form method="POST" action="{{ route('users.update', $user) }}" autocomplete="off">
    @csrf
    @method('PUT')
    <div class="card-body">@include('users._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('users.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
