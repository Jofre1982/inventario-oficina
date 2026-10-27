@extends('layouts.app', ['page' => 'Elementos', 'pageSlug' => 'items'])

@section('content')
@include('partials.page-header', ['title' => 'Editar elemento'])
<div class="card">
    <form method="POST" action="{{ route('items.update', $item) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="card-body">@include('item._form')</div>
    <div class="card-footer d-flex justify-content-between">
        <div>
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('items.show', $item) }}" class="btn btn-default">Cancelar</a>
        </div>
    </div>
    </form>
</div>
@can('items.destroy')
    <div class="text-right">
        <form action="{{ route('items.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este elemento? Solo es posible si nunca ha tenido movimientos.')">
            @csrf @method('DELETE')
            <button class="btn btn-link btn-danger btn-sm"><i class="tim-icons icon-trash-simple"></i> Eliminar elemento</button>
        </form>
    </div>
@endcan
@endsection
