@extends('layouts.app', ['page' => 'Dependencias', 'pageSlug' => 'dependencies'])

@section('content')
@include('partials.page-header', ['title' => $dependency->name])
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <p><span class="text-muted">Jefe</span><br>{{ $dependency->head_name ?: '—' }}</p>
                <p><span class="text-muted">Teléfono</span><br>{{ $dependency->phone ?: '—' }}</p>
                <p class="mb-0"><span class="text-muted">Estado</span><br>{{ $dependency->active ? 'Activa' : 'Inactiva' }}</p>
            </div>
            <div class="card-footer">
                @can('dependencies.edit')<a href="{{ route('dependencies.edit', $dependency) }}" class="btn btn-primary btn-sm">Editar</a>@endcan
                @can('reports.movements')<a href="{{ route('reports.movements', ['mode' => 'dependency', 'dependency' => $dependency->id]) }}" class="btn btn-info btn-sm">Reporte</a>@endcan
                <a href="{{ route('dependencies.index') }}" class="btn btn-default btn-sm">Regresar</a>
            </div>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Funcionarios</h4>
                @can('responsibles.create')<a href="{{ route('responsibles.create', ['dependency' => $dependency->id]) }}" class="btn btn-link btn-sm">+ Agregar</a>@endcan
            </div>
            <div class="card-body">
                @forelse ($dependency->responsibles as $person)
                    <div class="stat-line">
                        <span>@can('responsibles.show')<a href="{{ route('responsibles.show', $person) }}">{{ $person->name }}</a>@else{{ $person->name }}@endcan
                            <br><small class="text-muted">{{ $person->position }}</small></span>
                        @unless ($person->active)<small class="text-muted">inactivo</small>@endunless
                    </div>
                @empty
                    <p class="text-muted mb-0">Sin funcionarios registrados.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Elementos prestados a la dependencia</h4></div>
            <div class="card-body">@include('partials.loans-table', ['loans' => $loans, 'showDependency' => false])</div>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Últimas salidas</h4>
                @can('deliveries.index')<a href="{{ route('deliveries.index', ['dependency' => $dependency->id]) }}" class="btn btn-link btn-sm">Ver todas</a>@endcan
            </div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>N.º</th><th>Fecha</th><th>Responsable</th><th class="text-right">Unidades</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($deliveries as $delivery)
                        <tr>
                            <td>@can('deliveries.show')<a href="{{ route('deliveries.show', $delivery) }}">{{ $delivery->number }}</a>@else{{ $delivery->number }}@endcan</td>
                            <td>{{ fecha($delivery->date) }}</td>
                            <td>{{ $delivery->responsible->name }}</td>
                            <td class="text-right">{{ $delivery->details->sum('quantity') }}</td>
                            <td>@include('partials.delivery-status')</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Sin salidas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
