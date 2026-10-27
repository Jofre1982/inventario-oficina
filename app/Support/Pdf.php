<?php

namespace App\Support;

use App\Models\Organization;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

/** Genera PDF a partir de una vista Blade (actas y reportes). */
class Pdf
{
    public static function render(string $view, array $data = [], string $orientation = 'portrait'): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('chroot', [public_path(), base_path('vendor/dompdf/dompdf')]);
        $options->set('tempDir', storage_path('framework/cache'));
        $options->set('fontDir', storage_path('framework/cache'));
        $options->set('fontCache', storage_path('framework/cache'));

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('letter', $orientation);
        $dompdf->loadHtml(view($view, $data + ['organization' => Organization::current()])->render(), 'UTF-8');
        $dompdf->render();

        // Número de página en el pie.
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 110, $canvas->get_height() - 28, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 7, [0.4, 0.4, 0.4]);

        return $dompdf->output();
    }

    /** Se abre en una ventana nueva para imprimir o guardar. */
    public static function inline(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        return new Response(static::render($view, $data, $orientation), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
