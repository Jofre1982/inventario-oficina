@extends('layouts.app', ['page' => 'Dependencias', 'pageSlug' => 'dependencies'])

@section('content')
@include('partials.page-header', ['title' => 'Dependencias', 'action' => route('dependencies.create'), 'can' => 'dependencies.create', 'label' => 'Nueva'])
<div class="card">
    <div class="card-body">
        @include('partials.search', ['placeholder' => 'Nombre de la dependencia'])
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Dependencia</th><th>Jefe</th><th>Teléfono</th><th class="text-right">Funcionarios</th><th class="text-right">Préstamos pendientes</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                <tbody>
                @forelse ($dependencies as $dependency)
                    <tr>
                        <td>@can('dependencies.show')<a href="{{ route('dependencies.show', $dependency) }}">{{ $dependency->name }}</a>@else{{ $dependency->name }}@endcan</td>
                        <td>{{ $dependency->head_name }}</td>
                        <td>{{ $dependency->phone }}</td>
                        <td class="text-right">{{ $dependency->responsibles_count }}</td>
                        <td class="text-right">{{ $dependency->pending_loans_count ?: '' }}</td>
                        <td>@if ($dependency->active)<span class="badge badge-success">Activa</span>@else<span class="badge badge-default">Inactiva</span>@endif</td>
                        <td class="text-right">
                            @can('dependencies.edit')
                                <a href="{{ route('dependencies.edit', $dependency) }}" class="btn btn-link btn-info btn-sm btn-icon" title="Editar"><i class="tim-icons icon-pencil"></i></a>
                            @endcan
                            @can('dependencies.destroy')
                                @include('partials.delete', ['action' => route('dependencies.destroy', $dependency)])
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No hay dependencias. Registre las oficinas de la entidad (Secretaría de Gobierno, Tesorería, etc.).</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $dependencies->links() }}
    </div>
</div>
@endsection
