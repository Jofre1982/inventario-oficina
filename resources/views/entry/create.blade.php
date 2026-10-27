@extends('layouts.app', ['page' => 'Nueva entrada', 'pageSlug' => 'entries'])

@section('content')
@include('partials.page-header', ['title' => 'Nueva entrada'])
<div class="alert alert-info">Al registrar la entrada, las cantidades se <b>suman al almacén</b>.</div>
<form method="POST" action="{{ route('entries.store') }}" autocomplete="off">
@csrf
<div class="row">
    <div class="col-lg-8">@include('partials.scan-box')</div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label for="date">Fecha *</label>
                    <input type="date" class="form-control" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label for="provider_id">Proveedor / origen</label>
                    <select class="form-control" name="provider_id" id="provider_id">
                        <option value="">— Ninguno —</option>
                        @foreach ($providers as $provider)
                            <option value="{{ $provider->id }}" @selected(old('provider_id') == $provider->id)>{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="document">Documento soporte</label>
                    <input type="text" class="form-control" name="document" id="document" value="{{ old('document') }}" maxlength="120" placeholder="Factura, contrato, orden de compra...">
                </div>
                <div class="form-group">
                    <label for="notes">Observaciones</label>
                    <textarea class="form-control" name="notes" id="notes" rows="3">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" id="save" class="btn btn-primary btn-block btn-lg" disabled>Registrar entrada</button>
                <a href="{{ route('entries.index') }}" class="btn btn-default btn-block">Cancelar</a>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('js')
<script>
    $(function () {
        new Inventario.LineEditor({
            mode: 'entry',
            lookupUrl: @json(route('items.lookup')),
            scan: '#scan', results: '#scan-results', body: '#lines', submit: '#save', summary: '#lines-summary',
            initial: @json($oldLines)
        });
        $('#scan').focus();
    });
</script>
@endpush
