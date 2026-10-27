@extends('layouts.app', ['page' => 'Responsables', 'pageSlug' => 'responsibles'])

@section('content')
@include('partials.page-header', ['title' => $responsible->name])
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <p><span class="text-muted">Cédula</span><br>{{ $responsible->document }}</p>
                <p><span class="text-muted">Cargo</span><br>{{ $responsible->position ?: '—' }}</p>
                <p><span class="text-muted">Dependencia</span><br>{{ $responsible->dependency->name ?? '—' }}</p>
                <p><span class="text-muted">Teléfono / correo</span><br>{{ $responsible->phone ?: '—' }} {{ $responsible->email ? '· ' . $responsible->email : '' }}</p>
                <p class="mb-0"><span class="text-muted">Estado</span><br>{{ $responsible->active ? 'Activo' : 'Inactivo' }}</p>
            </div>
            <div class="card-footer">
                @can('deliveries.create')
                    @if ($responsible->active)<a href="{{ route('deliveries.create', ['responsible' => $responsible->id]) }}" class="btn btn-success btn-sm">Entregar elementos</a>@endif
                @endcan
                @can('responsibles.edit')<a href="{{ route('responsibles.edit', $responsible) }}" class="btn btn-primary btn-sm">Editar</a>@endcan
                @can('reports.movements')<a href="{{ route('reports.movements', ['mode' => 'responsible', 'responsible' => $responsible->id]) }}" class="btn btn-info btn-sm">Reporte</a>@endcan
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Lo que tiene prestado ahora ({{ $loans->sum(fn ($l) => $l->pending) }} unidades)</h4></div>
            <div class="card-body">@include('partials.loans-table', ['loans' => $loans, 'showPerson' => false])</div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Historial de salidas</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>N.º</th><th>Fecha</th><th>Elementos</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($deliveries as $delivery)
                        <tr>
                            <td>@can('deliveries.show')<a href="{{ route('deliveries.show', $delivery) }}">{{ $delivery->number }}</a>@else{{ $delivery->number }}@endcan</td>
                            <td>{{ fecha($delivery->date) }}</td>
                            <td><small>{{ $delivery->details->map(fn ($d) => $d->quantity . ' ' . ($d->item->name ?? ''))->join(', ') }}</small></td>
                            <td>@include('partials.delivery-status')</td>
                            <td class="text-right">@can('deliveries.pdf')<a href="{{ route('deliveries.pdf', $delivery) }}" target="_blank" class="btn btn-link btn-primary btn-sm btn-icon" title="Acta"><i class="tim-icons icon-paper"></i></a>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Sin salidas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
                {{ $deliveries->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
