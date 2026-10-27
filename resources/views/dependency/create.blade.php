@extends('layouts.app', ['page' => 'Dependencias', 'pageSlug' => 'dependencies'])

@section('content')
@include('partials.page-header', ['title' => 'Nueva dependencia'])
<div class="card">
    <form method="POST" action="{{ route('dependencies.store') }}">
    @csrf
    <div class="card-body">@include('dependency._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Registrar</button>
        <a href="{{ route('dependencies.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
