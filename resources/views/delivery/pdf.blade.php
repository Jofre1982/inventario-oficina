@php
    $consumables = $delivery->details->where('type', \App\Models\Item::CONSUMABLE);
    $returnables = $delivery->details->where('type', \App\Models\Item::RETURNABLE);
    $title = $returnables->isNotEmpty() ? 'Acta de entrega y préstamo' : 'Acta de entrega';
    $keeper = $organization->keeper_name ?: ($delivery->user->name ?? '');
@endphp
@extends('partials.pdf-layout', ['title' => $title, 'number' => $delivery->number, 'subtitle' => 'Fecha: ' . fecha($delivery->date)])

@section('body')
@unless ($delivery->isValid())<div class="canceled">SALIDA ANULADA EL {{ fecha($delivery->canceled_at) }}</div>@endunless
<p>
    {{ $organization->city ? 'En ' . $organization->city . ', el' : 'El' }} {{ $delivery->date->translatedFormat('j \d\e F \d\e Y') }},
    el almacén de {{ $organization->name }} hace entrega de los elementos que se relacionan a continuación a:
</p>
<table class="info">
    <tr>
        <td style="width:40%"><span class="label">Responsable que recibe</span><b>{{ $delivery->responsible->name }}</b></td>
        <td style="width:20%"><span class="label">Cédula</span>{{ $delivery->responsible->document }}</td>
        <td style="width:40%"><span class="label">Cargo</span>{{ $delivery->responsible->position ?: '—' }}</td>
    </tr>
    <tr>
        <td><span class="label">Dependencia u oficina</span>{{ $delivery->dependency->name ?? '—' }}</td>
        <td><span class="label">Fecha de salida</span>{{ fecha($delivery->date) }}</td>
        <td><span class="label">Fecha de devolución (devolutivos)</span>
            @if ($delivery->due_date)<b>{{ fecha($delivery->due_date) }}</b>@else No aplica (solo consumibles) @endif</td>
    </tr>
</table>

<table class="items">
    <thead><tr><th style="width:24px">#</th><th style="width:95px">Código</th><th>Elemento</th><th style="width:70px">Unidad</th><th style="width:55px" class="r">Cantidad</th></tr></thead>
    <tbody>
    @php($n = 1)
    @foreach ([['Elementos devolutivos (en préstamo, deben devolverse)', $returnables], ['Elementos de consumo', $consumables]] as [$label, $rows])
        @continue($rows->isEmpty())
        <tr class="group"><td colspan="5">{{ $label }}</td></tr>
        @foreach ($rows as $detail)
            <tr>
                <td>{{ $n++ }}</td>
                <td>{{ $detail->item->code }}</td>
                <td>{{ $detail->item->name }}@if ($detail->item->serial)<br><span class="small muted">{{ $detail->item->serial }}</span>@endif</td>
                <td>{{ $detail->item->unit }}</td>
                <td class="r">{{ cantidad($detail->quantity) }}</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
<p class="small muted">Total: {{ $delivery->details->count() }} {{ $delivery->details->count() === 1 ? 'elemento' : 'elementos' }}, {{ cantidad($delivery->details->sum('quantity')) }} unidades.</p>

@if ($delivery->notes)
    <div class="box"><b>Observaciones:</b> {{ $delivery->notes }}</div>
@endif

<p class="terms">{{ $organization->terms }}</p>
@if ($returnables->isNotEmpty())
    <p class="terms"><b>Los elementos devolutivos deben devolverse a más tardar el {{ $delivery->due_date->translatedFormat('j \d\e F \d\e Y') }}.</b></p>
@endif

<table class="signatures">
    <tr>
        <td><div class="line"><b>ENTREGA</b><br>{{ $keeper }}<br>{{ $organization->keeper_title ?: 'Almacén' }}</div></td>
        <td><div class="line"><b>RECIBE</b><br>{{ $delivery->responsible->name }}<br>C.C. {{ $delivery->responsible->document }}</div></td>
        <td><div class="line"><b>VO. BO.</b><br>{{ $delivery->dependency->head_name ?: 'Jefe de la dependencia' }}<br>{{ $delivery->dependency->name ?? '' }}</div></td>
    </tr>
</table>
@endsection
