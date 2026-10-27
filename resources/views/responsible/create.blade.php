@extends('layouts.app', ['page' => 'Responsables', 'pageSlug' => 'responsibles'])

@section('content')
@include('partials.page-header', ['title' => 'Nuevo responsable'])
<div class="card">
    <form method="POST" action="{{ route('responsibles.store') }}">
    @csrf
    <div class="card-body">@include('responsible._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Registrar</button>
        <a href="{{ route('responsibles.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
