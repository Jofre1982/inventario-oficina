@extends('partials.pdf-layout', ['title' => 'Comprobante de entrada', 'number' => $entry->number, 'subtitle' => 'Fecha: ' . fecha($entry->date)])

@section('body')
@unless ($entry->isValid())<div class="canceled">ENTRADA ANULADA</div>@endunless
<table class="info">
    <tr>
        <td style="width:40%"><span class="label">Proveedor / origen</span>{{ $entry->provider->name ?? '—' }}{{ $entry->provider && $entry->provider->nit ? ' · NIT ' . $entry->provider->nit : '' }}</td>
        <td style="width:30%"><span class="label">Documento soporte</span>{{ $entry->document ?: '—' }}</td>
        <td style="width:30%"><span class="label">Registró</span>{{ $entry->user->name ?? '—' }}</td>
    </tr>
</table>
<table class="items">
    <thead><tr><th style="width:24px">#</th><th style="width:95px">Código</th><th>Elemento</th><th style="width:80px">Tipo</th><th style="width:70px">Unidad</th><th style="width:55px" class="r">Cantidad</th></tr></thead>
    <tbody>
    @foreach ($entry->details as $i => $detail)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $detail->item->code }}</td>
            <td>{{ $detail->item->name }}</td>
            <td>{{ $detail->item->type_label }}</td>
            <td>{{ $detail->item->unit }}</td>
            <td class="r">{{ cantidad($detail->quantity) }}</td>
        </tr>
    @endforeach
    <tr><td colspan="5" class="r"><b>Total unidades</b></td><td class="r"><b>{{ cantidad($entry->details->sum('quantity')) }}</b></td></tr>
    </tbody>
</table>
@if ($entry->notes)<div class="box"><b>Observaciones:</b> {{ $entry->notes }}</div>@endif
<table class="signatures">
    <tr>
        <td><div class="line"><b>ENTREGA</b><br>{{ $entry->provider->name ?? '' }}</div></td>
        <td><div class="line"><b>RECIBE</b><br>{{ $organization->keeper_name ?: ($entry->user->name ?? '') }}<br>{{ $organization->keeper_title ?: 'Almacén' }}</div></td>
    </tr>
</table>
@endsection
