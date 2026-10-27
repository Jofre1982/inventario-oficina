@extends('layouts.app', ['page' => 'Prestado por persona', 'pageSlug' => 'reports_loans'])

@section('content')
@include('partials.page-header', ['title' => 'Qué tiene prestado cada persona'])
<div class="card">
    <div class="card-body">
        <form method="GET" class="filters row align-items-end" autocomplete="off">
            <div class="col-md-4 form-group">
                <label>Persona</label>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Nombre o cédula">
            </div>
            <div class="col-md-4 form-group">
                <label>Dependencia</label>
                <select name="dependency" class="form-control">
                    <option value="">Todas</option>
                    @foreach ($dependencies as $d)
                        <option value="{{ $d->id }}" @selected(request('dependency') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group">
                <div class="form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" type="checkbox" name="overdue" value="1" @checked($overdueOnly)>
                        <span class="form-check-sign"><span class="check"></span></span>
                        Solo vencidos
                    </label>
                </div>
            </div>
            <div class="col-md-2 form-group">
                <button class="btn btn-primary btn-sm">Consultar</button>
                <a href="{{ request()->fullUrlWithQuery(['pdf' => 1]) }}" target="_blank" class="btn btn-default btn-sm"><i class="tim-icons icon-paper"></i> PDF</a>
            </div>
        </form>
        <p class="text-muted mb-0">{{ $people->count() }} {{ $people->count() === 1 ? 'persona tiene' : 'personas tienen' }} elementos prestados · {{ cantidad($people->sum('units')) }} unidades en total.</p>
    </div>
</div>

@forelse ($people as $p)
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h4 class="card-title mb-0">
                    @can('responsibles.show')<a href="{{ route('responsibles.show', $p['responsible']) }}">{{ $p['responsible']->name }}</a>@else{{ $p['responsible']->name }}@endcan
                    @if ($p['overdue'])<span class="badge badge-danger">{{ $p['overdue'] }} vencido{{ $p['overdue'] > 1 ? 's' : '' }}</span>@endif
                </h4>
                <small class="text-muted">CC {{ $p['responsible']->document }} · {{ $p['responsible']->position }} · {{ $p['responsible']->dependency->name ?? '' }}</small>
            </div>
            <span><b>{{ $p['units'] }}</b> {{ $p['units'] === 1 ? 'unidad' : 'unidades' }}</span>
        </div>
        <div class="card-body">@include('partials.loans-table', ['loans' => $p['lines'], 'showPerson' => false])</div>
    </div>
@empty
    <div class="card"><div class="card-body text-center text-muted">Nadie tiene elementos prestados{{ $overdueOnly ? ' vencidos' : '' }}.</div></div>
@endforelse
@endsection
