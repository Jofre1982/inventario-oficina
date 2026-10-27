<?php

use Illuminate\Support\Carbon;

if (! function_exists('fecha')) {
    /** 27/10/2026 (o "—" si no hay fecha). */
    function fecha($date, string $format = 'd/m/Y'): string
    {
        return $date ? Carbon::parse($date)->format($format) : '—';
    }
}

if (! function_exists('cantidad')) {
    /** Número entero con separador de miles: 1.250 */
    function cantidad($value): string
    {
        return number_format((int) $value, 0, ',', '.');
    }
}
