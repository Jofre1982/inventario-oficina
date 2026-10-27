@extends('partials.pdf-layout', ['title' => 'Elementos prestados por persona', 'subtitle' => 'Corte: ' . fecha($today)])

@section('body')
<p>{{ $people->count() }} {{ $people->count() === 1 ? 'persona tiene' : 'personas tienen' }} elementos prestados{{ $overdueOnly ? ' vencidos' : '' }}{{ $dependency ? ' en ' . $dependency->name : '' }} · {{ cantidad($people->sum('units')) }} unidades.</p>
<table class="items">
    <thead><tr><th>Elemento</th><th style="width:80px">Código</th><th class="r" style="width:45px">Cant.</th><th style="width:60px">Salida</th><th style="width:62px">Salió</th><th style="width:62px">Devolver</th><th style="width:85px">Estado</th></tr></thead>
    <tbody>
    @forelse ($people as $p)
        <tr class="group"><td colspan="7">{{ $p['responsible']->name }} · CC {{ $p['responsible']->document }} · {{ $p['responsible']->dependency->name ?? '' }} — {{ $p['units'] }} und.</td></tr>
        @foreach ($p['lines'] as $line)
            @php($due = $line->delivery->due_date)
            <tr>
                <td>{{ $line->item->name }}{{ $line->item->serial ? ' (' . $line->item->serial . ')' : '' }}</td>
                <td>{{ $line->item->code }}</td>
                <td class="r">{{ $line->pending }}</td>
                <td>{{ $line->delivery->number }}</td>
                <td>{{ fecha($line->delivery->date) }}</td>
                <td>{{ fecha($due) }}</td>
                <td>@if ($due && $due->lt($today))<span class="alert">Vencido {{ (int) $due->diffInDays($today) }} d</span>@else Al día @endif</td>
            </tr>
        @endforeach
    @empty
        <tr><td colspan="7" class="c muted">Nadie tiene elementos prestados.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
