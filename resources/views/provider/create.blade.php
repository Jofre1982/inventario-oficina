@extends('layouts.app', ['page' => 'Proveedores', 'pageSlug' => 'providers'])

@section('content')
@include('partials.page-header', ['title' => 'Nuevo proveedor'])
<div class="card">
    <form method="POST" action="{{ route('providers.store') }}">
    @csrf
    <div class="card-body">@include('provider._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Registrar</button>
        <a href="{{ route('providers.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
