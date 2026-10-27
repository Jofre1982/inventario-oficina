@extends('layouts.app', ['page' => 'Responsables', 'pageSlug' => 'responsibles'])

@section('content')
@include('partials.page-header', ['title' => 'Editar responsable'])
<div class="card">
    <form method="POST" action="{{ route('responsibles.update', $responsible) }}">
    @csrf
    @method('PUT')
    <div class="card-body">@include('responsible._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('responsibles.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
