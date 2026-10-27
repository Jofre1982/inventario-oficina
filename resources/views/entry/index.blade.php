@extends('layouts.app', ['page' => 'Entradas', 'pageSlug' => 'entries'])

@section('content')
@include('partials.page-header', ['title' => 'Entradas', 'action' => route('entries.create'), 'can' => 'entries.create', 'label' => 'Nueva entrada'])
<div class="card">
    <div class="card-body">
        @include('partials.search', ['placeholder' => 'N.º, documento, proveedor o elemento'])
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>N.º</th><th>Fecha</th><th>Proveedor / origen</th><th>Documento</th><th class="text-right">Unidades</th><th>Registró</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>@can('entries.show')<a href="{{ route('entries.show', $entry) }}">{{ $entry->number }}</a>@else{{ $entry->number }}@endcan</td>
                        <td>{{ fecha($entry->date) }}</td>
                        <td>{{ $entry->provider->name ?? '—' }}</td>
                        <td>{{ $entry->document }}</td>
                        <td class="text-right">{{ cantidad($entry->units) }}</td>
                        <td>{{ $entry->user->name ?? '—' }}</td>
                        <td>@if ($entry->isValid())<span class="badge badge-success">Válida</span>@else<span class="badge badge-danger">Anulada</span>@endif</td>
                        <td class="text-right">
                            @can('entries.pdf')<a href="{{ route('entries.pdf', $entry) }}" target="_blank" class="btn btn-link btn-primary btn-sm btn-icon" title="PDF"><i class="tim-icons icon-paper"></i></a>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No hay entradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $entries->links() }}
    </div>
</div>
@endsection
