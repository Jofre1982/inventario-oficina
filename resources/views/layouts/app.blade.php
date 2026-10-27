<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@isset($page){{ $page }} · @endisset{{ config('app.name', 'Inventario Oficina') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('black/img/favicon.png') }}">
    <link href="{{ asset('vendor/poppins/poppins.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('black/css/nucleo-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('black/css/black-dashboard.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/inventario.css') }}?v=2" rel="stylesheet">
    @stack('css')
</head>
<body class="{{ $class ?? '' }}">
    @auth
        <div class="wrapper">
            @include('layouts.navbars.sidebar')
            <div class="main-panel">
                @include('layouts.navbars.navbar')
                <div class="content">
                    @include('partials.flash')
                    @yield('content')
                </div>
                @include('layouts.footer')
            </div>
        </div>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
    @else
        <div class="wrapper wrapper-full-page">
            <div class="full-page {{ $contentClass ?? '' }}">
                <div class="content">
                    <div class="container">
                        @include('partials.flash')
                        @yield('content')
                    </div>
                </div>
                @include('layouts.footer')
            </div>
        </div>
    @endauth

    <script src="{{ asset('black/js/core/jquery.min.js') }}"></script>
    <script src="{{ asset('black/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('black/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('black/js/plugins/perfect-scrollbar.jquery.min.js') }}"></script>
    <script src="{{ asset('black/js/black-dashboard.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/inventario.js') }}?v=2"></script>
    @stack('js')
</body>
</html>
