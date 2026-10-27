@extends('layouts.app', ['page' => 'Categorías', 'pageSlug' => 'categories'])

@section('content')
@include('partials.page-header', ['title' => 'Categorías', 'action' => route('categories.create'), 'can' => 'categories.create', 'label' => 'Nueva'])
<div class="card">
    <div class="card-body">
        @include('partials.search', ['placeholder' => 'Nombre de la categoría'])
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Nombre</th><th>Descripción</th><th class="text-right">Elementos</th><th class="text-right">Acciones</th></tr></thead>
                <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td><a href="{{ route('items.index', ['category' => $category->id]) }}">{{ $category->name }}</a></td>
                        <td>{{ $category->description }}</td>
                        <td class="text-right">{{ $category->items_count }}</td>
                        <td class="text-right">
                            @can('categories.edit')
                                <a href="{{ route('categories.edit', $category) }}" class="btn btn-link btn-info btn-sm btn-icon" title="Editar"><i class="tim-icons icon-pencil"></i></a>
                            @endcan
                            @can('categories.destroy')
                                @include('partials.delete', ['action' => route('categories.destroy', $category)])
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">No hay categorías.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $categories->links() }}
    </div>
</div>
@endsection
