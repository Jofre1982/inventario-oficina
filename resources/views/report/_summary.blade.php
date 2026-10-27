{{-- Resumen por elemento: $summary (item_id => [tipo => total]), $summaryItems --}}
@php
    $cols = [
        \App\Models\Movement::ENTRY => 'Entradas',
        \App\Models\Movement::OUTPUT => 'Salidas consumo',
        \App\Models\Movement::LOAN => 'Préstamos',
        \App\Models\Movement::RETURN => 'Devoluciones',
        \App\Models\Movement::WRITE_OFF => 'Bajas',
    ];
@endphp
<table class="{{ $tableClass ?? 'table' }} report-summary">
    <thead><tr><th>Elemento</th><th>Tipo</th>@foreach ($cols as $label)<th class="text-right r">{{ $label }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse ($summaryItems as $it)
        <tr>
            <td>{{ $it->name }}</td>
            <td>{{ $it->type_label }}</td>
            @foreach ($cols as $type => $label)
                <td class="text-right r">{{ ($summary[$it->id][$type] ?? 0) ?: '' }}</td>
            @endforeach
        </tr>
    @empty
        <tr><td colspan="{{ count($cols) + 2 }}" class="text-center c text-muted muted">Sin movimientos en el periodo.</td></tr>
    @endforelse
    </tbody>
</table>
