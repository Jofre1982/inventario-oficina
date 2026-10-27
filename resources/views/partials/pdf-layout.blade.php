<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 34px 44px 34px; }
        * { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        body { margin: 0; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 0; text-transform: uppercase; }
        p { margin: 0 0 6px; line-height: 1.4; }
        .muted { color: #666; }
        .header { width: 100%; border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 12px; }
        .header td { vertical-align: middle; }
        .header .logo { width: 70px; }
        .header .logo img { max-height: 60px; max-width: 66px; }
        .doc { text-align: right; }
        .doc .number { font-size: 15px; font-weight: bold; }
        .info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info td { vertical-align: top; padding: 5px 7px; border: 1px solid #bbb; }
        .info .label { display: block; font-size: 8px; color: #666; text-transform: uppercase; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.items th { background: #3a3a3a; color: #fff; padding: 5px; text-align: left; font-size: 9px; }
        table.items td { padding: 4px 5px; border-bottom: 1px solid #ccc; vertical-align: top; }
        table.items tr.group td { background: #eee; font-weight: bold; }
        .r, table.items th.r { text-align: right; }
        .c { text-align: center; }
        .small { font-size: 8px; }
        .box { border: 1px solid #bbb; padding: 6px 8px; margin-bottom: 10px; }
        .terms { text-align: justify; font-size: 9px; }
        .canceled { color: #c00; font-weight: bold; font-size: 13px; text-align: center; border: 2px solid #c00; padding: 4px; margin-bottom: 10px; }
        .alert { color: #b00; font-weight: bold; }
        table.signatures { width: 100%; margin-top: 46px; page-break-inside: avoid; }
        table.signatures td { width: 33%; padding: 0 12px; vertical-align: top; text-align: center; }
        table.signatures .line { border-top: 1px solid #222; padding-top: 4px; }
        .footer-note { margin-top: 18px; text-align: center; font-size: 8px; color: #777; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            @if ($organization->logo_path)
                <td class="logo"><img src="{{ $organization->logo_path }}"></td>
            @endif
            <td>
                <h1>{{ $organization->name }}</h1>
                @if ($organization->nit)<div>NIT {{ $organization->nit }}</div>@endif
                <div class="muted">{{ collect([$organization->address, $organization->city, $organization->phone])->filter()->join(' · ') }}</div>
            </td>
            <td class="doc">
                <h2>{{ $title }}</h2>
                @isset($number)<div class="number">N.º {{ $number }}</div>@endisset
                @isset($subtitle)<div class="muted">{{ $subtitle }}</div>@endisset
            </td>
        </tr>
    </table>
    @yield('body')
    <div class="footer-note">Generado el {{ now()->format('d/m/Y h:i a') }} · {{ config('app.name') }}</div>
</body>
</html>
