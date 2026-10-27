{{-- Tabla de devolutivos pendientes. Variables: $loans (DeliveryDetail con delivery, item), $showPerson, $showDependency, $showItem --}}
@php($today = \Illuminate\Support\Carbon::today())
<div class="table-responsive">
    <table class="table">
        <thead><tr>
            <th>Salida</th>
            @if ($showPerson ?? true)<th>Responsable</th>@endif
            @if ($showDependency ?? true)<th>Dependencia</th>@endif
            @if ($showItem ?? true)<th>Elemento</th>@endif<th class="text-right">Pendiente</th><th>Salió</th><th>Debe devolver</th><th></th>
        </tr></thead>
        <tbody>
        @forelse ($loans as $loan)
            @php($due = $loan->delivery->due_date)
            @php($late = $due && $due->lt($today))
            @php($days = $due ? (int) abs($due->diffInDays($today)) : 0)
            <tr @class(['row-overdue' => $late])>
                <td>@can('deliveries.show')<a href="{{ route('deliveries.show', $loan->delivery) }}">N.º {{ $loan->delivery->number }}</a>@else N.º {{ $loan->delivery->number }} @endcan</td>
                @if ($showPerson ?? true)
                    <td>@can('responsibles.show')<a href="{{ route('responsibles.show', $loan->delivery->responsible) }}">{{ $loan->delivery->responsible->name }}</a>@else{{ $loan->delivery->responsible->name }}@endcan</td>
                @endif
                @if ($showDependency ?? true)<td>{{ $loan->delivery->dependency->name ?? '—' }}</td>@endif
                @if ($showItem ?? true)<td>{{ $loan->item->name }}<br><small class="text-muted">{{ $loan->item->code }}{{ $loan->item->serial ? ' · ' . $loan->item->serial : '' }}</small></td>@endif
                <td class="text-right">{{ $loan->pending }}</td>
                <td>{{ fecha($loan->delivery->date) }}</td>
                <td>
                    {{ fecha($due) }}<br>
                    @if ($late)
                        <small class="text-overdue">Vencido hace {{ $days }} {{ $days === 1 ? 'día' : 'días' }}</small>
                    @elseif ($due && $due->isToday())
                        <small class="text-warning">Vence hoy</small>
                    @elseif ($due)
                        <small class="text-muted">Faltan {{ $days }} {{ $days === 1 ? 'día' : 'días' }}</small>
                    @endif
                </td>
                <td class="text-right">
                    @can('returns.create')
                        <a href="{{ route('returns.create', $loan->delivery) }}" class="btn btn-sm btn-success">Recibir</a>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted">No hay préstamos pendientes.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
