@extends('layouts.app', ['page' => 'Devoluciones', 'pageSlug' => 'returns'])

@section('content')
@include('partials.page-header', ['title' => 'Devoluciones'])
<div class="card">
    <div class="card-body">
        <p class="text-muted">Para registrar una devolución vaya a <a href="{{ route('loans.index') }}">Préstamos</a> y pulse <b>Recibir</b>, o ábrala desde la salida.</p>
        <form method="GET" class="form-inline mb-3" autocomplete="off">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control mr-2" placeholder="N.º, salida, responsable o cédula">
            <select name="condition" class="form-control mr-2" onchange="this.form.submit()">
                <option value="">Cualquier estado</option>
                @foreach (\App\Models\ItemReturnDetail::CONDITIONS as $value => $label)
                    <option value="{{ $value }}" @selected(request('condition') === $value)>Con elementos en estado {{ mb_strtolower($label) }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-default">Buscar</button>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>N.º</th><th>Fecha</th><th>Salida</th><th>Responsable</th><th>Dependencia</th><th>Elementos</th><th class="text-right"></th></tr></thead>
                <tbody>
                @forelse ($returns as $return)
                    <tr>
                        <td>@can('returns.show')<a href="{{ route('returns.show', $return) }}">{{ $return->number }}</a>@else{{ $return->number }}@endcan</td>
                        <td>{{ fecha($return->date) }}</td>
                        <td>@can('deliveries.show')<a href="{{ route('deliveries.show', $return->delivery) }}">{{ $return->delivery->number }}</a>@else{{ $return->delivery->number }}@endcan</td>
                        <td>{{ $return->delivery->responsible->name }}</td>
                        <td>{{ $return->delivery->dependency->name ?? '—' }}</td>
                        <td>{{ $return->details->sum('quantity') }}
                            @foreach ($return->details->pluck('condition')->unique() as $c)
                                <span class="badge badge-cond-{{ $c }}">{{ \App\Models\ItemReturnDetail::CONDITIONS[$c] }}</span>
                            @endforeach
                        </td>
                        <td class="text-right">@can('returns.pdf')<a href="{{ route('returns.pdf', $return) }}" target="_blank" class="btn btn-link btn-primary btn-sm btn-icon" title="Acta de devolución"><i class="tim-icons icon-paper"></i></a>@endcan</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No hay devoluciones.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $returns->links() }}
    </div>
</div>
@endsection
