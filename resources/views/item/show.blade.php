@extends('layouts.app', ['page' => 'Elementos', 'pageSlug' => 'items'])

@section('content')
@php($loaned = $item->loaned_quantity)
@include('partials.page-header', ['title' => $item->name])
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                @if ($item->image_url)
                    <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="item-photo mb-3">
                @else
                    <div class="item-thumb-empty mb-3" style="width:100%;height:160px"><i class="tim-icons icon-components" style="font-size:3rem"></i></div>
                @endif
                <div>
                    <span class="badge badge-type-{{ $item->type }}">{{ $item->type_label }}</span>
                    @unless ($item->active)<span class="badge badge-default">Inactivo</span>@endunless
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="stat-line"><span>Disponible en almacén</span><b class="{{ $item->is_low ? 'text-warning' : 'text-success' }}">{{ cantidad($item->stock) }}</b></div>
                @if ($item->isReturnable() || $loaned)
                    <div class="stat-line"><span>Prestado</span><b>{{ cantidad($loaned) }}</b></div>
                @endif
                @if ($item->isReturnable() || $item->unavailable_stock)
                    <div class="stat-line"><span>No disponible (dañado / incompleto)</span><b class="{{ $item->unavailable_stock ? 'text-danger' : '' }}">{{ cantidad($item->unavailable_stock) }}</b></div>
                    <div class="stat-line"><span>Total de la entidad</span><b>{{ cantidad($item->stock + $loaned + $item->unavailable_stock) }}</b></div>
                @endif
                <p class="text-muted small mt-2 mb-0">Unidad: {{ $item->unit }}{{ $item->min_stock ? ' · mínimo ' . $item->min_stock : '' }}</p>
            </div>
            <div class="card-footer">
                @can('items.edit')<a href="{{ route('items.edit', $item) }}" class="btn btn-primary btn-sm">Editar</a>@endcan
                @can('deliveries.create')
                    @if ($item->active && $item->stock > 0)<a href="{{ route('deliveries.create') }}" class="btn btn-success btn-sm">Entregar</a>@endif
                @endcan
                <a href="{{ route('items.index') }}" class="btn btn-default btn-sm">Regresar</a>
            </div>
        </div>

        @can('items.adjust')
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Ajustes</h4></div>
                <div class="card-body">
                    @if ($item->unavailable_stock > 0)
                        <form method="POST" action="{{ route('items.repair', $item) }}" class="mb-4">
                            @csrf
                            <p class="mb-1"><b>Reparado:</b> pasar unidades no disponibles a disponibles.</p>
                            <div class="form-row">
                                <div class="col-4"><input type="number" name="quantity" min="1" max="{{ $item->unavailable_stock }}" value="1" class="form-control" required></div>
                                <div class="col-8"><input type="text" name="notes" class="form-control" placeholder="Observación (opcional)" maxlength="200"></div>
                            </div>
                            <button class="btn btn-sm btn-success mt-2">Marcar como reparado</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('items.writeoff', $item) }}" onsubmit="return confirm('¿Dar de baja estas unidades? Saldrán del inventario.')">
                        @csrf
                        <p class="mb-1"><b>Dar de baja</b> (pérdida, daño sin arreglo, vencimiento...).</p>
                        <div class="form-row">
                            <div class="col-4"><input type="number" name="quantity" min="1" value="1" class="form-control" required></div>
                            <div class="col-8">
                                <select name="from" class="form-control">
                                    <option value="available">de las disponibles ({{ $item->stock }})</option>
                                    @if ($item->unavailable_stock)<option value="unavailable" selected>de las no disponibles ({{ $item->unavailable_stock }})</option>@endif
                                </select>
                            </div>
                        </div>
                        <input type="text" name="reason" class="form-control mt-2" placeholder="Motivo *" maxlength="200" required>
                        <button class="btn btn-sm btn-danger mt-2">Dar de baja</button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6"><p><span class="text-muted">Código</span><br>{{ $item->code }}</p></div>
                    <div class="col-sm-6"><p><span class="text-muted">Categoría</span><br>{{ $item->category->name ?? '—' }}</p></div>
                    <div class="col-sm-6"><p><span class="text-muted">Serial / placa</span><br>{{ $item->serial ?: '—' }}</p></div>
                    <div class="col-sm-6"><p><span class="text-muted">Ubicación</span><br>{{ $item->location ?: '—' }}</p></div>
                    @if ($item->description)
                        <div class="col-12"><p class="mb-0"><span class="text-muted">Descripción</span><br>{!! nl2br(e($item->description)) !!}</p></div>
                    @endif
                </div>
            </div>
        </div>

        @if ($item->isReturnable() || $loans->isNotEmpty())
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">¿Quién lo tiene?</h4></div>
                <div class="card-body">@include('partials.loans-table', ['loans' => $loans, 'showItem' => false])</div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Historial de movimientos</h4><small class="text-muted">En el orden en que se registraron; “Saldo disp.” es lo que quedó disponible después de cada uno.</small></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Fecha</th><th>Movimiento</th><th>Documento</th><th>Responsable / detalle</th><th class="text-right">Cantidad</th><th class="text-right">Saldo disp.</th></tr></thead>
                        <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ fecha($movement->date) }}</td>
                                <td>{{ $movement->type_label }}
                                    @if ($movement->condition)<span class="badge badge-cond-{{ $movement->condition }}">{{ $movement->condition_label }}</span>@endif</td>
                                <td><small>{{ $movement->reference }}</small></td>
                                <td><small>{{ $movement->responsible->name ?? '' }}{{ $movement->dependency ? ' · ' . $movement->dependency->name : '' }}{{ $movement->notes ? ' ' . $movement->notes : '' }}</small></td>
                                <td class="text-right {{ $movement->direction > 0 ? 'text-success' : ($movement->direction < 0 ? 'text-danger' : '') }}">
                                    {{ $movement->direction > 0 ? '+' : ($movement->direction < 0 ? '−' : '') }}{{ $movement->quantity }}
                                </td>
                                <td class="text-right">{{ $movement->stock_after }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Todavía no tiene movimientos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $movements->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
