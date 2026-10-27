@extends('layouts.app', ['page' => 'Roles', 'pageSlug' => 'roles'])

@section('content')
@include('partials.page-header', ['title' => 'Nuevo rol'])
<div class="card">
    <form method="POST" action="{{ route('roles.store') }}">
    @csrf

    <div class="card-body">@include('role._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Registrar</button>
        <a href="{{ route('roles.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
