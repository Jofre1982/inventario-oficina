@extends('layouts.app', ['page' => 'Inicio', 'pageSlug' => 'dashboard'])

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Hola, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') ?: auth()->user()->name }}</h2>
    <div>
        @can('deliveries.create')
            <a href="{{ route('deliveries.create') }}" class="btn btn-primary"><i class="tim-icons icon-send"></i> Nueva salida / préstamo</a>
        @endcan
        @can('entries.create')
            <a href="{{ route('entries.create') }}" class="btn btn-default"><i class="tim-icons icon-cart"></i> Nueva entrada</a>
        @endcan
    </div>
</div>

@can('loans.index')
    @if ($overdue->isNotEmpty())
        <div class="alert alert-danger alert-overdue d-flex flex-wrap justify-content-between align-items-center">
            <span><i class="tim-icons icon-bell-55"></i>
                <b>{{ $overdue->sum(fn ($l) => $l->pending) }} {{ $overdue->sum(fn ($l) => $l->pending) === 1 ? 'elemento prestado está vencido' : 'elementos prestados están vencidos' }}</b>
                ({{ $overdueResponsibles }} {{ $overdueResponsibles === 1 ? 'persona' : 'personas' }}).</span>
            <a href="{{ route('loans.overdue') }}" class="btn btn-sm btn-neutral">Ver vencidos</a>
        </div>
    @endif
@endcan

<div class="row">
    <div class="col-lg-3 col-md-6"><div class="card kpi {{ $overdue->isNotEmpty() ? 'kpi-danger' : '' }}"><div class="card-body">
        <p class="kpi-label">Préstamos vencidos</p>
        <p class="kpi-value {{ $overdue->isNotEmpty() ? 'text-danger' : 'text-success' }}">{{ $overdue->sum(fn ($l) => $l->pending) }}</p>
        <small class="text-muted">{{ $overdue->isNotEmpty() ? 'unidades por recuperar' : 'todo al día' }}</small>
    </div></div></div>
    <div class="col-lg-3 col-md-6"><div class="card kpi"><div class="card-body">
        <p class="kpi-label">Prestado ahora</p>
        <p class="kpi-value">{{ cantidad($pendingUnits) }}</p>
        <small class="text-muted">unidades en {{ $activeLoans }} {{ $activeLoans === 1 ? 'préstamo' : 'préstamos' }}</small>
    </div></div></div>
    <div class="col-lg-3 col-md-6"><div class="card kpi"><div class="card-body">
        <p class="kpi-label">Salidas del mes</p>
        <p class="kpi-value">{{ $deliveriesMonth }}</p>
        <small class="text-muted">{{ cantidad($unitsOutMonth) }} unidades · {{ now()->translatedFormat('F Y') }}</small>
    </div></div></div>
    <div class="col-lg-3 col-md-6"><div class="card kpi {{ $lowStockCount ? 'kpi-warning' : '' }}"><div class="card-body">
        <p class="kpi-label">Elementos activos</p>
        <p class="kpi-value">{{ $itemCount }}</p>
        <small class="text-muted">{{ $consumableCount }} consumibles · {{ $returnableCount }} devolutivos</small>
        @if ($lowStockCount)<br><small class="text-warning">{{ $lowStockCount }} con stock bajo</small>@endif
    </div></div></div>
</div>

@can('loans.index')
    @if ($overdue->isNotEmpty())
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0 text-danger">Préstamos vencidos</h4>
                <a href="{{ route('loans.overdue') }}" class="btn btn-link btn-sm">Ver todos</a>
            </div>
            <div class="card-body">@include('partials.loans-table', ['loans' => $overdue->take(8)])</div>
        </div>
    @endif
@endcan

<div class="card card-chart">
    <div class="card-header">
        <h5 class="card-category">Últimos 14 días</h5>
        <h3 class="card-title">Unidades que salen y entran al almacén</h3>
    </div>
    <div class="card-body">
        <div class="chart-area"><canvas id="movesChart"></canvas></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Préstamos que vencen esta semana</h4>
                @can('loans.index')<a href="{{ route('loans.index') }}" class="btn btn-link btn-sm">Ver préstamos</a>@endcan
            </div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Responsable</th><th>Elemento</th><th class="text-right">Cant.</th><th>Vence</th></tr></thead>
                    <tbody>
                    @forelse ($dueSoon as $loan)
                        <tr>
                            <td>{{ $loan->delivery->responsible->name }}</td>
                            <td>{{ $loan->item->name }}</td>
                            <td class="text-right">{{ $loan->pending }}</td>
                            <td>{{ $loan->delivery->due_date->isToday() ? 'Hoy' : fecha($loan->delivery->due_date) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Ningún préstamo vence en los próximos 7 días.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Stock bajo</h4>
                @can('items.index')<a href="{{ route('items.index', ['low' => 1]) }}" class="btn btn-link btn-sm">Ver todos</a>@endcan
            </div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Elemento</th><th class="text-right">Disponible</th><th class="text-right">Mínimo</th></tr></thead>
                    <tbody>
                    @forelse ($lowStock as $item)
                        <tr>
                            <td>@can('items.show')<a href="{{ route('items.show', $item) }}">{{ $item->name }}</a>@else{{ $item->name }}@endcan</td>
                            <td class="text-right {{ $item->stock <= 0 ? 'text-danger' : 'text-warning' }}"><b>{{ $item->stock }}</b> <small class="text-muted">{{ $item->unit }}</small></td>
                            <td class="text-right">{{ $item->min_stock }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Todo el almacén está por encima del mínimo.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                @if ($unavailableCount)
                    <p class="mb-0 small">@can('items.index')<a href="{{ route('items.index', ['unavailable' => 1]) }}">{{ $unavailableCount }} {{ $unavailableCount === 1 ? 'elemento tiene' : 'elementos tienen' }} unidades dañadas o incompletas</a>@endcan</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Últimas salidas</h4>
                @can('deliveries.index')<a href="{{ route('deliveries.index') }}" class="btn btn-link btn-sm">Ver todas</a>@endcan
            </div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>N.º</th><th>Fecha</th><th>Responsable</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($latestDeliveries as $delivery)
                        <tr>
                            <td>@can('deliveries.show')<a href="{{ route('deliveries.show', $delivery) }}">{{ $delivery->number }}</a>@else{{ $delivery->number }}@endcan</td>
                            <td>{{ fecha($delivery->date) }}</td>
                            <td>{{ $delivery->responsible->name }}<br><small class="text-muted">{{ $delivery->dependency->name ?? '' }}</small></td>
                            <td>@include('partials.delivery-status')</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Aún no hay salidas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('black/js/plugins/chartjs.min.js') }}"></script>
<script>
    $(function () {
        var ctx = document.getElementById('movesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chart['labels']),
                datasets: [
                    { label: 'Salen (salidas y préstamos)', data: @json($chart['out']), backgroundColor: 'rgba(225,78,202,0.75)', borderColor: '#e14eca', borderWidth: 1 },
                    { label: 'Entran (entradas y devoluciones)', data: @json($chart['in']), backgroundColor: 'rgba(0,214,180,0.6)', borderColor: '#00d6b4', borderWidth: 1 }
                ]
            },
            options: {
                maintainAspectRatio: false,
                legend: { display: true, labels: { fontColor: '#9a9a9a' } },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true, precision: 0, fontColor: '#9a9a9a' }, gridLines: { color: 'rgba(29,140,248,0.1)' } }],
                    xAxes: [{ ticks: { fontColor: '#9a9a9a' }, gridLines: { display: false } }]
                }
            }
        });
    });
</script>
@endpush
