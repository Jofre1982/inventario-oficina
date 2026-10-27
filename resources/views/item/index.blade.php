@extends('layouts.app', ['page' => 'Elementos', 'pageSlug' => 'items'])

@section('content')
@include('partials.page-header', ['title' => 'Elementos', 'action' => route('items.create'), 'can' => 'items.create'])
<div class="card">
    <div class="card-body">
        <form method="GET" class="filters row align-items-end" autocomplete="off">
            <div class="col-md-4 form-group">
                <label>Buscar</label>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Nombre, código de barras o placa" autofocus>
            </div>
            <div class="col-md-2 form-group">
                <label>Tipo</label>
                <select name="type" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Item::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}s</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label>Categoría</label>
                <select name="category" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label>Estado</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">Activos</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactivos</option>
                    <option value="all" @selected(request('status') === 'all')>Todos</option>
                </select>
            </div>
            <div class="col-12 mb-3">
                <button class="btn btn-sm btn-default" type="submit"><i class="tim-icons icon-zoom-split"></i> Buscar</button>
                @if (request()->boolean('low'))
                    <a href="{{ route('items.index', request()->except(['low', 'page'])) }}" class="btn btn-sm btn-warning">Mostrando stock bajo · ver todos</a>
                @else
                    <a href="{{ route('items.index', request()->except('page') + ['low' => 1]) }}" class="btn btn-sm btn-default">Solo stock bajo</a>
                @endif
                @if (request()->boolean('unavailable'))
                    <a href="{{ route('items.index', request()->except(['unavailable', 'page'])) }}" class="btn btn-sm btn-danger">Mostrando dañados/incompletos · ver todos</a>
                @else
                    <a href="{{ route('items.index', request()->except('page') + ['unavailable' => 1]) }}" class="btn btn-sm btn-default">Dañados / incompletos</a>
                @endif
                @if (request()->hasAny(['q', 'type', 'category', 'status', 'low', 'unavailable']))
                    <a href="{{ route('items.index') }}" class="btn btn-sm btn-link">Limpiar filtros</a>
                @endif
            </div>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th></th><th>Código</th><th>Elemento</th><th>Tipo</th><th>Categoría</th><th class="text-right">Disponible</th><th class="text-right">Prestado</th><th class="text-right">No disponible</th><th class="text-right">Acciones</th></tr></thead>
                <tbody>
                @forelse ($items as $item)
                    <tr @class(['text-muted' => ! $item->active])>
                        <td>
                            @if ($item->image_url)<img src="{{ $item->image_url }}" class="item-thumb" alt="">@else<span class="item-thumb-empty"><i class="tim-icons icon-components"></i></span>@endif
                        </td>
                        <td><small>{{ $item->code }}</small></td>
                        <td>@can('items.show')<a href="{{ route('items.show', $item) }}">{{ $item->name }}</a>@else{{ $item->name }}@endcan
                            @if ($item->serial)<br><small class="text-muted">{{ $item->serial }}</small>@endif
                            @unless ($item->active)<span class="badge badge-default">inactivo</span>@endunless
                        </td>
                        <td><span class="badge badge-type-{{ $item->type }}">{{ $item->type_label }}</span></td>
                        <td>{{ $item->category->name ?? '—' }}</td>
                        <td class="text-right">
                            <span class="{{ $item->is_low ? 'text-warning font-weight-bold' : '' }}" title="{{ $item->min_stock ? 'Mínimo: ' . $item->min_stock : '' }}">{{ cantidad($item->stock) }}</span>
                            <small class="text-muted">{{ $item->unit }}</small>
                        </td>
                        <td class="text-right">{{ $item->isReturnable() ? cantidad($item->loaned_quantity) : '' }}</td>
                        <td class="text-right">@if ($item->unavailable_stock)<span class="text-danger">{{ $item->unavailable_stock }}</span>@endif</td>
                        <td class="text-right">
                            @can('change.status.items')
                                <form action="{{ route('change.status.items', $item) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-link btn-sm btn-icon {{ $item->active ? 'btn-warning' : 'btn-success' }}" title="{{ $item->active ? 'Desactivar' : 'Activar' }}">
                                        <i class="tim-icons {{ $item->active ? 'icon-button-pause' : 'icon-button-power' }}"></i>
                                    </button>
                                </form>
                            @endcan
                            @can('items.edit')
                                <a href="{{ route('items.edit', $item) }}" class="btn btn-link btn-info btn-sm btn-icon" title="Editar"><i class="tim-icons icon-pencil"></i></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No hay elementos{{ request('q') ? ' que coincidan con «' . request('q') . '»' : '' }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>
@endsection
