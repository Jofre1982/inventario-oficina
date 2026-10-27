@extends('layouts.app', ['page' => 'Categorías', 'pageSlug' => 'categories'])

@section('content')
@include('partials.page-header', ['title' => 'Editar categoría'])
<div class="card">
    <form method="POST" action="{{ route('categories.update', $category) }}">
    @csrf
    @method('PUT')
    <div class="card-body">@include('category._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('categories.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
