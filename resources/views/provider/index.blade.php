@extends('layouts.app', ['page' => 'Proveedores', 'pageSlug' => 'providers'])

@section('content')
@include('partials.page-header', ['title' => 'Proveedores', 'action' => route('providers.create'), 'can' => 'providers.create'])
<div class="card">
    <div class="card-body">
        <p class="text-muted">Opcional: de dónde vienen los elementos que entran al almacén (contratistas, donantes...).</p>
        @include('partials.search', ['placeholder' => 'Nombre o NIT'])
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Nombre</th><th>NIT</th><th>Teléfono</th><th>Correo</th><th class="text-right">Entradas</th><th class="text-right">Acciones</th></tr></thead>
                <tbody>
                @forelse ($providers as $provider)
                    <tr>
                        <td>@can('entries.index')<a href="{{ route('entries.index', ['provider' => $provider->id]) }}">{{ $provider->name }}</a>@else{{ $provider->name }}@endcan</td>
                        <td>{{ $provider->nit }}</td>
                        <td>{{ $provider->phone }}</td>
                        <td>{{ $provider->email }}</td>
                        <td class="text-right">{{ $provider->entries_count }}</td>
                        <td class="text-right">
                            @can('providers.edit')
                                <a href="{{ route('providers.edit', $provider) }}" class="btn btn-link btn-info btn-sm btn-icon" title="Editar"><i class="tim-icons icon-pencil"></i></a>
                            @endcan
                            @can('providers.destroy')
                                @include('partials.delete', ['action' => route('providers.destroy', $provider)])
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No hay proveedores.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $providers->links() }}
    </div>
</div>
@endsection
