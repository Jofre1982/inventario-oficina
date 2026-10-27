@php($keeper = $organization->keeper_name ?: ($return->user->name ?? ''))
@extends('partials.pdf-layout', ['title' => 'Acta de devolución', 'number' => $return->number, 'subtitle' => 'Fecha: ' . fecha($return->date)])

@section('body')
<p>
    {{ $organization->city ? 'En ' . $organization->city . ', el' : 'El' }} {{ $return->date->translatedFormat('j \d\e F \d\e Y') }},
    el almacén de {{ $organization->name }} recibe los elementos devolutivos prestados en la salida N.º {{ $return->delivery->number }}
    del {{ fecha($return->delivery->date) }}, en el estado que se indica:
</p>
<table class="info">
    <tr>
        <td style="width:40%"><span class="label">Responsable que devuelve</span><b>{{ $return->delivery->responsible->name }}</b></td>
        <td style="width:20%"><span class="label">Cédula</span>{{ $return->delivery->responsible->document }}</td>
        <td style="width:40%"><span class="label">Dependencia</span>{{ $return->delivery->dependency->name ?? '—' }}</td>
    </tr>
    <tr>
        <td><span class="label">Fecha límite de devolución</span>{{ fecha($return->delivery->due_date) }}</td>
        <td><span class="label">Fecha de devolución</span>{{ fecha($return->date) }}</td>
        <td><span class="label">Oportunidad</span>
            @if ($return->delivery->due_date && $return->date->gt($return->delivery->due_date))
                @php($late = (int) $return->delivery->due_date->diffInDays($return->date))
                <span class="alert">Devuelto con {{ $late }} {{ $late === 1 ? 'día' : 'días' }} de retraso</span>
            @else
                A tiempo
            @endif
        </td>
    </tr>
</table>
<table class="items">
    <thead><tr><th style="width:24px">#</th><th style="width:95px">Código</th><th>Elemento</th><th style="width:55px" class="r">Cantidad</th><th style="width:70px">Estado</th><th>Observación</th></tr></thead>
    <tbody>
    @foreach ($return->details as $i => $detail)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $detail->item->code }}</td>
            <td>{{ $detail->item->name }}@if ($detail->item->serial)<br><span class="small muted">{{ $detail->item->serial }}</span>@endif</td>
            <td class="r">{{ $detail->quantity }}</td>
            <td><b>{{ $detail->condition_label }}</b></td>
            <td>{{ $detail->notes }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@php($pending = $return->delivery->details->where('type', \App\Models\Item::RETURNABLE)->filter(fn ($d) => $d->pending > 0))
@if ($pending->isNotEmpty())
    <div class="box"><b>Pendiente por devolver de esta salida (a la fecha de impresión):</b>
        {{ $pending->map(fn ($d) => $d->pending . ' ' . $d->item->name)->join(', ') }}.
    </div>
@else
    <div class="box">Con esta devolución <b>no quedan elementos pendientes</b> de la salida N.º {{ $return->delivery->number }}.</div>
@endif
@if ($return->notes)
    <div class="box"><b>Observaciones:</b> {{ $return->notes }}</div>
@endif
<table class="signatures">
    <tr>
        <td><div class="line"><b>DEVUELVE</b><br>{{ $return->delivery->responsible->name }}<br>C.C. {{ $return->delivery->responsible->document }}</div></td>
        <td><div class="line"><b>RECIBE</b><br>{{ $keeper }}<br>{{ $organization->keeper_title ?: 'Almacén' }}</div></td>
    </tr>
</table>
@endsection
