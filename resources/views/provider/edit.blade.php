@extends('layouts.app', ['page' => 'Proveedores', 'pageSlug' => 'providers'])

@section('content')
@include('partials.page-header', ['title' => 'Editar proveedor'])
<div class="card">
    <form method="POST" action="{{ route('providers.update', $provider) }}">
    @csrf
    @method('PUT')
    <div class="card-body">@include('provider._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('providers.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
