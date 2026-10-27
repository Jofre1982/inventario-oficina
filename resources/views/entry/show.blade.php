@extends('layouts.app', ['page' => 'Entradas', 'pageSlug' => 'entries'])

@section('content')
@include('partials.page-header', ['title' => 'Entrada N.º ' . $entry->number])
@unless ($entry->isValid())<div class="alert alert-danger">Esta entrada fue anulada el {{ fecha($entry->canceled_at, 'd/m/Y h:i a') }}.</div>@endunless
<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><span class="text-muted">Fecha</span><br>{{ fecha($entry->date) }}</div>
            <div class="col-md-3"><span class="text-muted">Proveedor / origen</span><br>{{ $entry->provider->name ?? '—' }}</div>
            <div class="col-md-3"><span class="text-muted">Documento soporte</span><br>{{ $entry->document ?: '—' }}</div>
            <div class="col-md-3"><span class="text-muted">Registró</span><br>{{ $entry->user->name ?? '—' }}</div>
        </div>
        @if ($entry->notes)<p><span class="text-muted">Observaciones:</span> {{ $entry->notes }}</p>@endif
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Código</th><th>Elemento</th><th>Tipo</th><th class="text-right">Cantidad</th></tr></thead>
                <tbody>
                @foreach ($entry->details as $detail)
                    <tr>
                        <td>{{ $detail->item->code }}</td>
                        <td>@can('items.show')<a href="{{ route('items.show', $detail->item) }}">{{ $detail->item->name }}</a>@else{{ $detail->item->name }}@endcan</td>
                        <td><span class="badge badge-type-{{ $detail->item->type }}">{{ $detail->item->type_label }}</span></td>
                        <td class="text-right">{{ cantidad($detail->quantity) }} <small class="text-muted">{{ $detail->item->unit }}</small></td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr><th colspan="3" class="text-right">Total unidades</th><th class="text-right">{{ cantidad($entry->details->sum('quantity')) }}</th></tr></tfoot>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-between">
        <div>
            @can('entries.pdf')<a href="{{ route('entries.pdf', $entry) }}" target="_blank" class="btn btn-primary btn-sm"><i class="tim-icons icon-paper"></i> Comprobante PDF</a>@endcan
            @can('change.status.entries')
                @if ($entry->isValid())
                    <form action="{{ route('entries.cancel', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Anular la entrada N.º {{ $entry->number }}? Sus cantidades se descontarán del almacén. No se puede deshacer.')">
                        @csrf @method('PATCH')
                        <button class="btn btn-danger btn-sm">Anular entrada</button>
                    </form>
                @endif
            @endcan
        </div>
        <a href="{{ route('entries.index') }}" class="btn btn-default btn-sm">Regresar</a>
    </div>
</div>
@endsection
