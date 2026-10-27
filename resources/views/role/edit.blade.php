@extends('layouts.app', ['page' => 'Roles', 'pageSlug' => 'roles'])

@section('content')
@include('partials.page-header', ['title' => 'Editar rol'])
<div class="card">
    <form method="POST" action="{{ route('roles.update', $role) }}">
    @csrf
    @method('PUT')
    <div class="card-body">@include('role._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('roles.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
