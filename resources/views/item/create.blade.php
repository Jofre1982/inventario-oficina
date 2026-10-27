@extends('layouts.app', ['page' => 'Elementos', 'pageSlug' => 'items'])

@section('content')
@include('partials.page-header', ['title' => 'Nuevo elemento'])
<div class="card">
    <form method="POST" action="{{ route('items.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="card-body">@include('item._form')</div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Registrar</button>
        <a href="{{ route('items.index') }}" class="btn btn-default">Cancelar</a>
    </div>
    </form>
</div>
@endsection
