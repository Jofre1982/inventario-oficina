@extends('partials.pdf-layout', ['title' => $title, 'subtitle' => fecha($from) . ' a ' . fecha($to)])

@section('body')
<table class="info">
    <tr>
        <td><span class="label">Periodo</span>{{ fecha($from) }} a {{ fecha($to) }}</td>
        <td><span class="label">Responsable</span>{{ $responsible ? $responsible->name . ' (CC ' . $responsible->document . ')' : 'Todos' }}</td>
        <td><span class="label">Dependencia</span>{{ $dependency->name ?? 'Todas' }}</td>
        <td><span class="label">Movimiento</span>{{ \App\Models\Movement::TYPES[$filters['type'] ?? ''] ?? 'Todos' }}</td>
        <td><span class="label">Elemento</span>{{ $item->name ?? 'Todos' }}</td>
    </tr>
</table>
<h2 style="font-size:11px;margin:8px 0 4px">Resumen por elemento</h2>
@include('report._summary', ['tableClass' => 'items'])

<h2 style="font-size:11px;margin:12px 0 4px">Detalle</h2>
<table class="items">
    <thead><tr><th>Fecha</th><th>Movimiento</th><th>Documento</th><th>Elemento</th><th class="r">Cant.</th><th>Responsable</th><th>Dependencia</th></tr></thead>
    <tbody>
    @forelse ($movements as $m)
        <tr>
            <td>{{ fecha($m->date) }}</td>
            <td>{{ $m->type_label }}{{ $m->condition ? ' (' . $m->condition_label . ')' : '' }}</td>
            <td>{{ $m->reference }}</td>
            <td>{{ $m->item->name }}</td>
            <td class="r">{{ $m->quantity }}</td>
            <td>{{ $m->responsible->name ?? '' }}</td>
            <td>{{ $m->dependency->name ?? '' }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="c muted">No hay movimientos con estos filtros.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
