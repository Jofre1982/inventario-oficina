@extends('layouts.app', ['page' => 'Entidad', 'pageSlug' => 'organization'])

@section('content')
@include('partials.page-header', ['title' => 'Datos de la entidad'])
<div class="card">
    <form method="POST" action="{{ route('organization.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    @php($canEdit = auth()->user()->can('organization.edit'))
    <fieldset @disabled(! $canEdit)>
    <div class="card-body">
        <p class="text-muted">Estos datos aparecen en el menú, en las actas de entrega y devolución y en los reportes.</p>
        <div class="row">
            <div class="col-md-8 form-group">
                <label for="name">Nombre de la alcaldía u oficina *</label>
                <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $organization->name) }}" required>
            </div>
            <div class="col-md-4 form-group">
                <label for="nit">NIT</label>
                <input type="text" class="form-control" name="nit" id="nit" value="{{ old('nit', $organization->nit) }}">
            </div>
            <div class="col-md-4 form-group">
                <label for="city">Municipio</label>
                <input type="text" class="form-control" name="city" id="city" value="{{ old('city', $organization->city) }}">
            </div>
            <div class="col-md-8 form-group">
                <label for="address">Dirección</label>
                <input type="text" class="form-control" name="address" id="address" value="{{ old('address', $organization->address) }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="phone">Teléfono</label>
                <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $organization->phone) }}">
            </div>
            <div class="col-md-6 form-group">
                <label for="email">Correo</label>
                <input type="email" class="form-control" name="email" id="email" value="{{ old('email', $organization->email) }}">
            </div>
        </div>
        <h4 class="mt-3 mb-2">Firmas de las actas</h4>
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="keeper_name">Nombre de quien entrega (almacenista)</label>
                <input type="text" class="form-control" name="keeper_name" id="keeper_name" value="{{ old('keeper_name', $organization->keeper_name) }}" placeholder="Si lo deja vacío se usa el usuario que registra">
            </div>
            <div class="col-md-6 form-group">
                <label for="keeper_title">Cargo de quien entrega</label>
                <input type="text" class="form-control" name="keeper_title" id="keeper_title" value="{{ old('keeper_title', $organization->keeper_title) }}" placeholder="Almacenista">
            </div>
            <div class="col-12 form-group">
                <label for="delivery_terms">Texto de compromiso que sale en el acta de entrega</label>
                <textarea class="form-control" name="delivery_terms" id="delivery_terms" rows="4" maxlength="2000">{{ old('delivery_terms', $organization->terms) }}</textarea>
            </div>
            <div class="col-md-6 form-group">
                <label class="d-block">Logo o escudo</label>
                @if ($organization->logo_url)
                    <img src="{{ $organization->logo_url }}" alt="Logo" class="logo-preview mb-2 d-block">
                @endif
                <input type="file" name="picture" accept="image/*">
            </div>
        </div>
    </div>
    @if ($canEdit)
        <div class="card-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
    @endif
    </fieldset>
    </form>
</div>
@endsection
