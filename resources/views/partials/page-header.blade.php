{{-- Uso: @include('partials.page-header', ['title' => 'Productos', 'action' => route('products.create'), 'can' => 'products.create', 'label' => 'Nuevo']) --}}
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3 page-header">
    <h2 class="mb-0">{{ $title }}</h2>
    <div>
        {{ $slot ?? '' }}
        @if (!empty($action) && (empty($can) || auth()->user()->can($can)))
            <a class="btn btn-primary btn-sm" href="{{ $action }}">
                <i class="tim-icons icon-simple-add"></i> {{ $label ?? 'Nuevo' }}
            </a>
        @endif
    </div>
</div>
