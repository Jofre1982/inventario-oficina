@extends('layouts.app', ['page' => 'Salidas', 'pageSlug' => 'deliveries'])

@section('content')
@include('partials.page-header', ['title' => 'Salida N.º ' . $delivery->number])
@unless ($delivery->isValid())<div class="alert alert-danger">Esta salida fue anulada el {{ fecha($delivery->canceled_at, 'd/m/Y h:i a') }}; los elementos volvieron al almacén.</div>@endunless
@if ($delivery->isOverdue())
    <div class="alert alert-danger alert-overdue">Préstamo <b>vencido hace {{ $delivery->overdueDays() }} {{ $delivery->overdueDays() === 1 ? 'día' : 'días' }}</b>: debía devolverse el {{ fecha($delivery->due_date) }}.</div>
@endif
<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><span class="text-muted">Responsable</span><br>
                @can('responsibles.show')<a href="{{ route('responsibles.show', $delivery->responsible) }}">{{ $delivery->responsible->name }}</a>@else{{ $delivery->responsible->name }}@endcan
                <br><small>CC {{ $delivery->responsible->document }}{{ $delivery->responsible->position ? ' · ' . $delivery->responsible->position : '' }}</small></div>
            <div class="col-md-3"><span class="text-muted">Dependencia</span><br>{{ $delivery->dependency->name ?? '—' }}</div>
            <div class="col-md-2"><span class="text-muted">Fecha de salida</span><br>{{ fecha($delivery->date) }}</div>
            <div class="col-md-2"><span class="text-muted">Debe devolver</span><br>{{ $delivery->due_date ? fecha($delivery->due_date) : 'No aplica' }}</div>
            <div class="col-md-2"><span class="text-muted">Estado</span><br>@include('partials.delivery-status')</div>
        </div>
        @if ($delivery->notes)<p><span class="text-muted">Observaciones:</span> {{ $delivery->notes }}</p>@endif
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Código</th><th>Elemento</th><th>Tipo</th><th class="text-right">Entregado</th><th class="text-right">Devuelto</th><th class="text-right">Pendiente</th></tr></thead>
                <tbody>
                @foreach ($delivery->details as $detail)
                    <tr>
                        <td>{{ $detail->item->code }}</td>
                        <td>@can('items.show')<a href="{{ route('items.show', $detail->item) }}">{{ $detail->item->name }}</a>@else{{ $detail->item->name }}@endcan
                            @if ($detail->item->serial)<br><small class="text-muted">{{ $detail->item->serial }}</small>@endif</td>
                        <td><span class="badge badge-type-{{ $detail->type }}">{{ $detail->type_label }}</span></td>
                        <td class="text-right">{{ cantidad($detail->quantity) }} <small class="text-muted">{{ $detail->item->unit }}</small></td>
                        <td class="text-right">{{ $detail->isReturnable() ? $detail->returned : '—' }}</td>
                        <td class="text-right">@if ($detail->isReturnable())<b class="{{ $detail->pending && $delivery->isOverdue() ? 'text-overdue' : '' }}">{{ $detail->pending }}</b>@else — @endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-muted small mb-0">Registró: {{ $delivery->user->name ?? '—' }} · {{ fecha($delivery->created_at, 'd/m/Y h:i a') }}</p>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-between">
        <div>
            @can('deliveries.pdf')<a href="{{ route('deliveries.pdf', $delivery) }}" target="_blank" class="btn btn-primary"><i class="tim-icons icon-paper"></i> Acta de entrega (PDF)</a>@endcan
            @can('returns.create')
                @if ($delivery->isValid() && $delivery->pendingQuantity() > 0)
                    <a href="{{ route('returns.create', $delivery) }}" class="btn btn-success"><i class="tim-icons icon-refresh-01"></i> Registrar devolución</a>
                @endif
            @endcan
            @can('change.status.deliveries')
                @if ($delivery->isValid() && $delivery->returns->isEmpty())
                    <form action="{{ route('deliveries.cancel', $delivery) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Anular la salida N.º {{ $delivery->number }}? Todos los elementos vuelven al almacén. No se puede deshacer.')">
                        @csrf @method('PATCH')
                        <button class="btn btn-link btn-danger">Anular salida</button>
                    </form>
                @endif
            @endcan
        </div>
        <a href="{{ route('deliveries.index') }}" class="btn btn-default btn-sm align-self-center">Regresar</a>
    </div>
</div>

@if ($delivery->returns->isNotEmpty())
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Devoluciones</h4></div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>N.º</th><th>Fecha</th><th>Elementos y estado</th><th>Recibió</th><th></th></tr></thead>
                <tbody>
                @foreach ($delivery->returns as $return)
                    <tr>
                        <td>@can('returns.show')<a href="{{ route('returns.show', $return) }}">{{ $return->number }}</a>@else{{ $return->number }}@endcan</td>
                        <td>{{ fecha($return->date) }}</td>
                        <td>@foreach ($return->details as $d)
                                <div>{{ $d->quantity }} {{ $d->item->name }} <span class="badge badge-cond-{{ $d->condition }}">{{ $d->condition_label }}</span>{{ $d->notes ? ' — ' . $d->notes : '' }}</div>
                            @endforeach</td>
                        <td>{{ $return->user->name ?? '—' }}</td>
                        <td class="text-right">@can('returns.pdf')<a href="{{ route('returns.pdf', $return) }}" target="_blank" class="btn btn-link btn-primary btn-sm btn-icon" title="Acta de devolución"><i class="tim-icons icon-paper"></i></a>@endcan</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

@if (session('open_acta') && auth()->user()->can('deliveries.pdf'))
    @push('js')
    <script>
        $(function () {
            Swal.fire({
                icon: 'success',
                title: 'Entrega registrada',
                text: '¿Abrir el acta de entrega para imprimir y firmar?',
                showCancelButton: true,
                confirmButtonText: 'Abrir acta',
                cancelButtonText: 'Ahora no'
            }).then(function (r) { if (r.isConfirmed) window.open(@json(route('deliveries.pdf', $delivery)), '_blank'); });
        });
    </script>
    @endpush
@endif
