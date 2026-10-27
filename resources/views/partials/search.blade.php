{{-- Buscador de listados: envía ?q= a la misma página --}}
<form method="GET" class="form-inline mb-3 search-form">
    @foreach (request()->except(['q', 'page']) as $key => $value)
        @if (!is_array($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    <input type="search" name="q" value="{{ request('q') }}" class="form-control mr-2" placeholder="{{ $placeholder ?? 'Buscar...' }}" autocomplete="off">
    <button class="btn btn-sm btn-default" type="submit"><i class="tim-icons icon-zoom-split"></i> Buscar</button>
    @if (request('q'))
        <a href="{{ url()->current() }}" class="btn btn-sm btn-link">Limpiar</a>
    @endif
</form>
