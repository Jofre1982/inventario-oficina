@php
    $user = auth()->user();
    $overdueCount = $user->can('loans.index') ? \App\Models\Delivery::overdue()->count() : 0;
    $menu = [
        'principal' => ['PRINCIPAL', 'icon-tap-02', [
            ['dashboard', 'home', [], 'INICIO', 'icon-laptop', null],
            ['profile', 'profile.edit', [], 'MI PERFIL', 'icon-satisfied', null],
        ]],
        'movimientos' => ['MOVIMIENTOS', 'icon-refresh-02', [
            ['deliveries_create', 'deliveries.create', [], 'NUEVA SALIDA / PRÉSTAMO', 'icon-send', 'deliveries.create'],
            ['deliveries', 'deliveries.index', [], 'SALIDAS', 'icon-delivery-fast', 'deliveries.index'],
            ['loans', 'loans.index', [], 'PRÉSTAMOS', 'icon-time-alarm', 'loans.index'],
            ['loans_overdue', 'loans.overdue', [], 'VENCIDOS', 'icon-alert-circle-exc', 'loans.index'],
            ['returns', 'returns.index', [], 'DEVOLUCIONES', 'icon-refresh-01', 'returns.index'],
            ['entries', 'entries.index', [], 'ENTRADAS', 'icon-cart', 'entries.index'],
        ]],
        'almacen' => ['ALMACÉN', 'icon-app', [
            ['items', 'items.index', [], 'ELEMENTOS', 'icon-components', 'items.index'],
            ['categories', 'categories.index', [], 'CATEGORÍAS', 'icon-tag', 'categories.index'],
            ['providers', 'providers.index', [], 'PROVEEDORES', 'icon-bus-front-12', 'providers.index'],
        ]],
        'personas' => ['PERSONAS', 'icon-single-02', [
            ['responsibles', 'responsibles.index', [], 'RESPONSABLES', 'icon-badge', 'responsibles.index'],
            ['dependencies', 'dependencies.index', [], 'DEPENDENCIAS', 'icon-bank', 'dependencies.index'],
        ]],
        'reportes' => ['REPORTES', 'icon-chart-bar-32', [
            ['reports_date', 'reports.movements', ['mode' => 'date'], 'POR FECHAS', 'icon-calendar-60', 'reports.movements'],
            ['reports_responsible', 'reports.movements', ['mode' => 'responsible'], 'POR RESPONSABLE', 'icon-single-02', 'reports.movements'],
            ['reports_dependency', 'reports.movements', ['mode' => 'dependency'], 'POR DEPENDENCIA', 'icon-bank', 'reports.movements'],
            ['reports_loans', 'reports.loans', [], 'PRESTADO POR PERSONA', 'icon-notes', 'reports.loans'],
        ]],
        'administrar' => ['ADMINISTRAR', 'icon-settings-gear-63', [
            ['users', 'users.index', [], 'USUARIOS', 'icon-badge', 'users.index'],
            ['roles', 'roles.index', [], 'ROLES', 'icon-key-25', 'roles.index'],
            ['organization', 'organization.index', [], 'ENTIDAD', 'icon-bank', 'organization.index'],
        ]],
    ];
    $current = $pageSlug ?? '';
@endphp
<div class="sidebar">
    <div class="sidebar-wrapper">
        <div class="logo">
            <a href="{{ route('home') }}" class="simple-text logo-normal" title="{{ \App\Models\Organization::current()->name }}">
                <i class="tim-icons icon-components"></i>
                {{ \Illuminate\Support\Str::limit(\App\Models\Organization::current()->name, 22) }}
            </a>
        </div>
        <ul class="nav">
            @foreach ($menu as $id => [$title, $icon, $entries])
                @php($visible = array_filter($entries, fn ($i) => $i[5] === null || $user->can($i[5])))
                @continue(empty($visible))
                @php($open = collect($visible)->contains(fn ($i) => $i[0] === $current) || ($id === 'movimientos' && $current === ''))
                <li>
                    <a data-toggle="collapse" href="#menu-{{ $id }}" aria-expanded="{{ $open ? 'true' : 'false' }}">
                        <i class="tim-icons {{ $icon }}"></i>
                        <span class="nav-link-text">{{ $title }}</span>
                        <b class="caret mt-1"></b>
                    </a>
                    <div class="collapse {{ $open ? 'show' : '' }}" id="menu-{{ $id }}">
                        <ul class="nav pl-4">
                            @foreach ($visible as [$slug, $route, $params, $label, $itemIcon])
                                <li @class(['active' => $current === $slug])>
                                    <a href="{{ route($route, $params) }}">
                                        <i class="tim-icons {{ $itemIcon }}"></i>
                                        <p>{{ $label }}
                                            @if ($slug === 'loans_overdue' && $overdueCount)
                                                <span class="badge badge-danger badge-pill ml-1">{{ $overdueCount }}</span>
                                            @endif
                                        </p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
