@extends('layouts.app', ['page' => 'Responsables', 'pageSlug' => 'responsibles'])

@section('content')
@include('partials.page-header', ['title' => 'Responsables', 'action' => route('responsibles.create'), 'can' => 'responsibles.create'])
<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-start">
            @include('partials.search', ['placeholder' => 'Nombre o cédula'])
            <form method="GET" class="form-inline mb-3 ml-md-3">
                @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                <select name="dependency" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">Todas las dependencias</option>
                    @foreach ($dependencies as $dependency)
                        <option value="{{ $dependency->id }}" @selected(request('dependency') == $dependency->id)>{{ $dependency->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Cédula</th><th>Nombre</th><th>Cargo</th><th>Dependencia</th><th class="text-right">Préstamos pendientes</th><th class="text-right">Acciones</th></tr></thead>
                <tbody>
                @forelse ($responsibles as $responsible)
                    <tr @class(['text-muted' => ! $responsible->active])>
                        <td>{{ $responsible->document }}</td>
                        <td>@can('responsibles.show')<a href="{{ route('responsibles.show', $responsible) }}">{{ $responsible->name }}</a>@else{{ $responsible->name }}@endcan
                            @unless ($responsible->active)<span class="badge badge-default">inactivo</span>@endunless</td>
                        <td>{{ $responsible->position }}</td>
                        <td>{{ $responsible->dependency->name ?? '—' }}</td>
                        <td class="text-right">{{ $responsible->pending_loans_count ?: '' }}</td>
                        <td class="text-right">
                            @can('deliveries.create')
                                @if ($responsible->active)
                                    <a href="{{ route('deliveries.create', ['responsible' => $responsible->id]) }}" class="btn btn-link btn-success btn-sm btn-icon" title="Entregarle elementos"><i class="tim-icons icon-send"></i></a>
                                @endif
                            @endcan
                            @can('responsibles.edit')
                                <a href="{{ route('responsibles.edit', $responsible) }}" class="btn btn-link btn-info btn-sm btn-icon" title="Editar"><i class="tim-icons icon-pencil"></i></a>
                            @endcan
                            @can('responsibles.destroy')
                                @include('partials.delete', ['action' => route('responsibles.destroy', $responsible)])
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No hay responsables.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $responsibles->links() }}
    </div>
</div>
@endsection
