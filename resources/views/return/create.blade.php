@extends('layouts.app', ['page' => 'Registrar devolución', 'pageSlug' => 'returns'])

@section('content')
@include('partials.page-header', ['title' => 'Devolución de la salida N.º ' . $delivery->number])
<form method="POST" action="{{ route('returns.store', $delivery) }}" autocomplete="off" id="return-form">
@csrf
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-5"><span class="text-muted">Responsable</span><br><b>{{ $delivery->responsible->name }}</b><br><small>CC {{ $delivery->responsible->document }}</small></div>
                    <div class="col-md-4"><span class="text-muted">Dependencia</span><br>{{ $delivery->dependency->name ?? '—' }}</div>
                    <div class="col-md-3"><span class="text-muted">Debía devolver</span><br>
                        <span class="{{ $delivery->isOverdue() ? 'text-overdue' : '' }}">{{ fecha($delivery->due_date) }}</span></div>
                </div>
                <p class="text-muted">Escriba cuántas unidades regresan y en qué estado. Lo que regresa <b>bueno</b> queda disponible; lo <b>dañado</b> o <b>incompleto</b> queda en el almacén como no disponible hasta repararlo o darlo de baja.</p>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Elemento</th><th class="text-right">Pendiente</th><th style="width:110px">Devuelve</th><th style="width:150px">Estado</th><th>Observación</th></tr></thead>
                        <tbody>
                        @php($i = 0)
                        @foreach ($delivery->details->where('type', \App\Models\Item::RETURNABLE) as $detail)
                            @continue($detail->pending === 0)
                            <tr class="return-line">
                                <td><input type="hidden" name="lines[{{ $i }}][delivery_detail_id]" value="{{ $detail->id }}">
                                    <b>{{ $detail->item->name }}</b><br><small class="text-muted">{{ $detail->item->code }}{{ $detail->item->serial ? ' · ' . $detail->item->serial : '' }}</small></td>
                                <td class="text-right">{{ $detail->pending }}</td>
                                <td><input type="number" class="form-control" name="lines[{{ $i }}][quantity]" min="0" max="{{ $detail->pending }}" value="{{ old("lines.$i.quantity", $detail->pending) }}"></td>
                                <td>
                                    <select class="form-control" name="lines[{{ $i }}][condition]">
                                        @foreach (\App\Models\ItemReturnDetail::CONDITIONS as $value => $label)
                                            <option value="{{ $value }}" @selected(old("lines.$i.condition", 'GOOD') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" class="form-control" name="lines[{{ $i }}][notes]" value="{{ old("lines.$i.notes") }}" maxlength="255" placeholder="Ej.: sin cargador"></td>
                            </tr>
                            @php($i++)
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0">Si un mismo elemento regresa una parte buena y otra dañada, registre primero una devolución y luego otra con el resto.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label for="date">Fecha de devolución *</label>
                    <input type="date" class="form-control" name="date" id="date" value="{{ old('date', now()->toDateString()) }}" min="{{ $delivery->date->toDateString() }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label for="notes">Observaciones</label>
                    <textarea class="form-control" name="notes" id="notes" rows="3">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="btn btn-success btn-block btn-lg">Registrar devolución</button>
                <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-default btn-block">Cancelar</a>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('js')
<script>
    $('#return-form').on('submit', function (e) {
        var total = 0;
        $(this).find('input[name$="[quantity]"]').each(function () { total += parseInt(this.value, 10) || 0; });
        if (!total) {
            e.preventDefault();
            Swal.fire({ icon: 'error', text: 'Escriba la cantidad que se devuelve de al menos un elemento.' });
        }
    });
</script>
@endpush
