@extends('layouts.app', ['page' => 'Categorías', 'pageSlug' => 'categories'])

@section('content')
@include('partials.page-header', ['title' => 'Nueva categoría'])
<div class="card">
    <form method="POST" action="{{ route('categories.store') }}">
    @csrf
    <div class="card-body">@include('category._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Registrar</button>
        <a href="{{ route('categories.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
