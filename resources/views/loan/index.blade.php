@extends('layouts.app', ['page' => $overdueOnly ? 'Préstamos vencidos' : 'Préstamos', 'pageSlug' => $overdueOnly ? 'loans_overdue' : 'loans'])

@section('content')
@include('partials.page-header', ['title' => $overdueOnly ? 'Préstamos vencidos' : 'Préstamos pendientes de devolución'])
@if ($overdueOnly)
    <div class="alert alert-danger alert-overdue">Elementos devolutivos que ya debían haber regresado al almacén.</div>
@endif
<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('loans.index') }}" class="filters row align-items-end" autocomplete="off">
            <div class="col-md-4 form-group">
                <label>Buscar</label>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Elemento, placa, responsable o cédula">
            </div>
            <div class="col-md-4 form-group">
                <label>Dependencia</label>
                <select name="dependency" class="form-control">
                    <option value="">Todas</option>
                    @foreach ($dependencies as $dependency)
                        <option value="{{ $dependency->id }}" @selected(request('dependency') == $dependency->id)>{{ $dependency->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group">
                <label>Estado</label>
                <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <option value="overdue" @selected($overdueOnly)>Vencidos</option>
                    <option value="on_time" @selected(request('status') === 'on_time')>Al día</option>
                </select>
            </div>
            <div class="col-md-2 form-group">
                <button class="btn btn-sm btn-primary">Filtrar</button>
            </div>
        </form>
        <p class="text-muted">{{ $loans->total() }} {{ $loans->total() === 1 ? 'elemento pendiente' : 'elementos pendientes' }}.
            @can('reports.loans')<a href="{{ route('reports.loans', $overdueOnly ? ['overdue' => 1] : []) }}">Ver agrupado por persona</a>@endcan</p>
        @include('partials.loans-table', ['loans' => $loans])
        {{ $loans->links() }}
    </div>
</div>
@endsection
