@extends('layouts.app', ['page' => 'Salidas', 'pageSlug' => 'deliveries'])

@section('content')
@include('partials.page-header', ['title' => 'Salidas y préstamos', 'action' => route('deliveries.create'), 'can' => 'deliveries.create', 'label' => 'Nueva salida / préstamo'])
<div class="card">
    <div class="card-body">
        <form method="GET" class="filters row align-items-end" autocomplete="off">
            <div class="col-md-4 form-group">
                <label>Buscar</label>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="N.º, responsable, cédula o elemento">
            </div>
            <div class="col-md-4 form-group">
                <label>Dependencia</label>
                <select name="dependency" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach ($dependencies as $dependency)
                        <option value="{{ $dependency->id }}" @selected(request('dependency') == $dependency->id)>{{ $dependency->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group">
                <label>Estado</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <option value="pending" @selected(request('status') === 'pending')>Con préstamos pendientes</option>
                    <option value="overdue" @selected(request('status') === 'overdue')>Vencidas</option>
                    <option value="canceled" @selected(request('status') === 'canceled')>Anuladas</option>
                </select>
            </div>
            <div class="col-md-2 form-group">
                <button class="btn btn-sm btn-default" type="submit">Buscar</button>
                @if (request()->hasAny(['q', 'dependency', 'status', 'responsible']))<a href="{{ route('deliveries.index') }}" class="btn btn-sm btn-link">Limpiar</a>@endif
            </div>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>N.º</th><th>Fecha</th><th>Responsable</th><th>Dependencia</th><th>Elementos</th><th>Devolver</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                <tbody>
                @forelse ($deliveries as $delivery)
                    <tr @class(['row-overdue' => $delivery->isOverdue()])>
                        <td>@can('deliveries.show')<a href="{{ route('deliveries.show', $delivery) }}">{{ $delivery->number }}</a>@else{{ $delivery->number }}@endcan</td>
                        <td>{{ fecha($delivery->date) }}</td>
                        <td>{{ $delivery->responsible->name }}</td>
                        <td>{{ $delivery->dependency->name ?? '—' }}</td>
                        <td>{{ $delivery->details->count() }} <small class="text-muted">({{ cantidad($delivery->details->sum('quantity')) }} und.)</small></td>
                        <td>{{ $delivery->due_date ? fecha($delivery->due_date) : '' }}</td>
                        <td>@include('partials.delivery-status')</td>
                        <td class="text-right">
                            @can('deliveries.pdf')<a href="{{ route('deliveries.pdf', $delivery) }}" target="_blank" class="btn btn-link btn-primary btn-sm btn-icon" title="Acta de entrega"><i class="tim-icons icon-paper"></i></a>@endcan
                            @can('returns.create')
                                @if ($delivery->isValid() && $delivery->pendingQuantity() > 0)
                                    <a href="{{ route('returns.create', $delivery) }}" class="btn btn-link btn-success btn-sm btn-icon" title="Registrar devolución"><i class="tim-icons icon-refresh-01"></i></a>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No hay salidas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $deliveries->links() }}
    </div>
</div>
@endsection
