@extends('layouts.app', ['page' => 'Dependencias', 'pageSlug' => 'dependencies'])

@section('content')
@include('partials.page-header', ['title' => 'Editar dependencia'])
<div class="card">
    <form method="POST" action="{{ route('dependencies.update', $dependency) }}">
    @csrf
    @method('PUT')
    <div class="card-body">@include('dependency._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('dependencies.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
