@extends('layouts.app', ['page' => 'Devoluciones', 'pageSlug' => 'returns'])

@section('content')
@include('partials.page-header', ['title' => 'Devolución N.º ' . $return->number])
<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><span class="text-muted">Fecha</span><br>{{ fecha($return->date) }}</div>
            <div class="col-md-3"><span class="text-muted">Salida</span><br>@can('deliveries.show')<a href="{{ route('deliveries.show', $return->delivery) }}">N.º {{ $return->delivery->number }}</a>@else N.º {{ $return->delivery->number }} @endcan del {{ fecha($return->delivery->date) }}</div>
            <div class="col-md-3"><span class="text-muted">Responsable</span><br>{{ $return->delivery->responsible->name }}<br><small>{{ $return->delivery->dependency->name ?? '' }}</small></div>
            <div class="col-md-3"><span class="text-muted">Recibió</span><br>{{ $return->user->name ?? '—' }}</div>
        </div>
        @if ($return->notes)<p><span class="text-muted">Observaciones:</span> {{ $return->notes }}</p>@endif
        <table class="table">
            <thead><tr><th>Elemento</th><th class="text-right">Cantidad</th><th>Estado</th><th>Observación</th></tr></thead>
            <tbody>
            @foreach ($return->details as $detail)
                <tr>
                    <td>{{ $detail->item->name }}<br><small class="text-muted">{{ $detail->item->code }}</small></td>
                    <td class="text-right">{{ $detail->quantity }}</td>
                    <td><span class="badge badge-cond-{{ $detail->condition }}">{{ $detail->condition_label }}</span></td>
                    <td>{{ $detail->notes }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <div>
            @can('returns.pdf')<a href="{{ route('returns.pdf', $return) }}" target="_blank" class="btn btn-primary btn-sm"><i class="tim-icons icon-paper"></i> Acta de devolución (PDF)</a>@endcan
            @can('returns.create')
                @if ($return->delivery->isValid() && $return->delivery->load('details')->pendingQuantity() > 0)
                    <a href="{{ route('returns.create', $return->delivery) }}" class="btn btn-success btn-sm">Registrar otra devolución de esta salida</a>
                @endif
            @endcan
        </div>
        <a href="{{ route('returns.index') }}" class="btn btn-default btn-sm">Regresar</a>
    </div>
</div>
@endsection
