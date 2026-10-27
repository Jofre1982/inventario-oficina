@extends('layouts.app', ['page' => $title, 'pageSlug' => 'reports_' . $mode])

@section('content')
@include('partials.page-header', ['title' => $title])
<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.movements', ['mode' => $mode]) }}" class="filters row align-items-end" autocomplete="off">
            @if ($mode === 'responsible')
                <div class="col-md-4 form-group">
                    <label>Responsable *</label>
                    <select name="responsible" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach ($responsibles as $r)
                            <option value="{{ $r->id }}" @selected(($filters['responsible'] ?? null) == $r->id)>{{ $r->name }} — {{ $r->document }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if ($mode === 'dependency' || $mode === 'date')
                <div class="col-md-4 form-group">
                    <label>Dependencia {{ $mode === 'dependency' ? '*' : '' }}</label>
                    <select name="dependency" class="form-control" @required($mode === 'dependency')>
                        <option value="">{{ $mode === 'dependency' ? 'Seleccione...' : 'Todas' }}</option>
                        @foreach ($dependencies as $d)
                            <option value="{{ $d->id }}" @selected(($filters['dependency'] ?? null) == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2 form-group">
                <label>Desde</label>
                <input class="form-control" type="date" name="from" value="{{ $from->toDateString() }}" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-2 form-group">
                <label>Hasta</label>
                <input class="form-control" type="date" name="to" value="{{ $to->toDateString() }}" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-3 form-group">
                <label>Movimiento</label>
                <select name="type" class="form-control">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Movement::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group">
                <label>Tipo de elemento</label>
                <select name="item_type" class="form-control">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Item::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['item_type'] ?? null) === $value)>{{ $label }}s</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 form-group">
                <label>Elemento</label>
                <select name="item" class="form-control">
                    <option value="">Todos</option>
                    @foreach ($items as $it)
                        <option value="{{ $it->id }}" @selected(($filters['item'] ?? null) == $it->id)>{{ $it->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group">
                <button type="submit" class="btn btn-primary">Consultar</button>
                @unless ($needsSelection)
                    <a href="{{ request()->fullUrlWithQuery(['pdf' => 1, 'page' => null]) }}" target="_blank" class="btn btn-default"><i class="tim-icons icon-paper"></i> PDF</a>
                @endunless
            </div>
        </form>
    </div>
</div>

@if ($needsSelection)
    <div class="alert alert-info">Seleccione {{ $mode === 'responsible' ? 'el responsable' : 'la dependencia' }} y pulse <b>Consultar</b>.</div>
@else
    <div class="row">
        @foreach ([\App\Models\Movement::ENTRY => ['Entradas', 'text-success'], \App\Models\Movement::OUTPUT => ['Salidas de consumo', ''], \App\Models\Movement::LOAN => ['Préstamos', 'text-warning'], \App\Models\Movement::RETURN => ['Devoluciones', 'text-info']] as $type => [$label, $class])
            <div class="col-lg-3 col-md-6"><div class="card kpi"><div class="card-body">
                <p class="kpi-label">{{ $label }}</p>
                <p class="kpi-value {{ $class }}">{{ cantidad($totals[$type]->total ?? 0) }} <small class="text-muted" style="font-size:.8rem">und.</small></p>
                <small class="text-muted">{{ $totals[$type]->records ?? 0 }} registros</small>
            </div></div></div>
        @endforeach
    </div>

    @if ($responsible)
        <div class="alert alert-default">
            <b>{{ $responsible->name }}</b> · CC {{ $responsible->document }} · {{ $responsible->dependency->name ?? '' }}
            @php($now = $responsible->pendingLoans())
            — hoy tiene prestadas <b>{{ $now->sum(fn ($l) => $l->pending) }}</b> unidades
            @can('responsibles.show')(<a href="{{ route('responsibles.show', $responsible) }}" class="alert-link">ver detalle</a>)@endcan.
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Resumen por elemento · {{ fecha($from) }} a {{ fecha($to) }}</h4></div>
        <div class="card-body"><div class="table-responsive">@include('report._summary')</div></div>
    </div>

    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Detalle de movimientos</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Movimiento</th><th>Documento</th><th>Elemento</th><th class="text-right">Cantidad</th><th>Responsable</th><th>Dependencia</th><th>Registró</th></tr></thead>
                    <tbody>
                    @forelse ($movements as $m)
                        <tr>
                            <td>{{ fecha($m->date) }}</td>
                            <td>{{ $m->type_label }} @if ($m->condition)<span class="badge badge-cond-{{ $m->condition }}">{{ $m->condition_label }}</span>@endif</td>
                            <td><small>
                                @if ($m->item_return_id && auth()->user()->can('returns.show'))<a href="{{ route('returns.show', $m->item_return_id) }}">{{ $m->reference }}</a>
                                @elseif ($m->delivery_id && auth()->user()->can('deliveries.show'))<a href="{{ route('deliveries.show', $m->delivery_id) }}">{{ $m->reference }}</a>
                                @elseif ($m->entry_id && auth()->user()->can('entries.show'))<a href="{{ route('entries.show', $m->entry_id) }}">{{ $m->reference }}</a>
                                @else {{ $m->reference }} @endif
                            </small></td>
                            <td>{{ $m->item->name }}</td>
                            <td class="text-right">{{ $m->quantity }}</td>
                            <td>{{ $m->responsible->name ?? '' }}</td>
                            <td>{{ $m->dependency->name ?? '' }}</td>
                            <td><small>{{ $m->user->name ?? '' }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No hay movimientos con estos filtros.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movements->links() }}
        </div>
    </div>
@endif
@endsection
