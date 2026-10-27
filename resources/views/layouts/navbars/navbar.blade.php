@php($overdueTotal = auth()->user()->can('loans.index') ? \App\Models\Delivery::overdue()->count() : 0)
<nav class="navbar navbar-expand-lg navbar-absolute navbar-transparent">
    <div class="container-fluid">
        <div class="navbar-wrapper">
            <div class="navbar-toggle d-inline">
                <button type="button" class="navbar-toggler">
                    <span class="navbar-toggler-bar bar1"></span>
                    <span class="navbar-toggler-bar bar2"></span>
                    <span class="navbar-toggler-bar bar3"></span>
                </button>
            </div>
            <span class="navbar-brand">{{ $page ?? 'Inicio' }}</span>
        </div>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navigation" aria-expanded="false" aria-label="Menú">
            <span class="navbar-toggler-bar navbar-kebab"></span>
            <span class="navbar-toggler-bar navbar-kebab"></span>
            <span class="navbar-toggler-bar navbar-kebab"></span>
        </button>
        <div class="collapse navbar-collapse" id="navigation">
            <ul class="navbar-nav ml-auto align-items-lg-center">
                @can('items.index')
                    <li class="nav-item">
                        <form method="GET" action="{{ route('items.index') }}" class="navbar-search" autocomplete="off">
                            <i class="tim-icons icon-zoom-split"></i>
                            <input type="search" name="q" class="form-control" placeholder="Código de barras o nombre" title="Escanee el código de un elemento para abrirlo">
                        </form>
                    </li>
                @endcan
                @if ($overdueTotal)
                    <li class="nav-item">
                        <a href="{{ route('loans.overdue') }}" class="nav-link text-danger" title="Salidas con elementos devolutivos que ya debían regresar">
                            <i class="tim-icons icon-bell-55"></i>
                            <b>{{ $overdueTotal }} {{ $overdueTotal === 1 ? 'préstamo vencido' : 'préstamos vencidos' }}</b>
                        </a>
                    </li>
                @endif
                <li class="dropdown nav-item">
                    <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">
                        <span class="user-initials">{{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join('') }}</span>
                        <b class="caret d-none d-lg-block d-xl-block"></b>
                        <p class="d-lg-none">{{ auth()->user()->name }}</p>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right dropdown-navbar">
                        <li class="nav-link"><span class="nav-item dropdown-item disabled text-muted">{{ auth()->user()->name }}</span></li>
                        <li class="nav-link"><a href="{{ route('profile.edit') }}" class="nav-item dropdown-item">Mi perfil</a></li>
                        <li class="dropdown-divider"></li>
                        <li class="nav-link">
                            <a href="#" class="nav-item dropdown-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Cerrar sesión</a>
                        </li>
                    </ul>
                </li>
                <li class="separator d-lg-none"></li>
            </ul>
        </div>
    </div>
</nav>
