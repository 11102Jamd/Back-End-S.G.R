<?php

/**
 * Servicio para la generación de documentos PDF.
 *
 * Este servicio encapsula la lógica de configuración y renderización de PDFs
 * utilizando la librería Dompdf. Está diseñado para ser utilizado por los
 * controladores que requieran exportar vistas Blade en formato PDF.
 *
 * @author Juan Alejandro Muñoz Devia
 */

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{

    /**
     * Genera un objeto PDF a partir de una vista Blade.
     *
     * Este método configura Dompdf, carga la vista y la renderiza en un
     * documento PDF. El objeto generado puede retornarse directamente al
     * navegador o almacenarse en disco.
     *
     * @param string $view Nombre de la vista Blade a renderizar.
     * @param array  $data Datos que se pasarán a la vista.
     * @param string $filename Nombre sugerido para el archivo PDF.
     *
     * @return \Dompdf\Dompdf Instancia de Dompdf con el documento renderizado.
     */
    public function generatePdf($view, $data = [], $fillename = 'document.pdf')
    {
        $options = new Options();

        // Permite cargar recursos externos
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        // Define la fuente por defecto
        $options->set('defaultFont', 'Arial');

        //Se crea la instancia de Dompdf con opciones personalizadas
        $dompdf = new Dompdf($options);

        // Carga erl contenido HTML de la vista blade
        $dompdf->loadHtml(view($view, $data)->render());

        // Definir el tamaño de la Hoja y orientacion
        $dompdf->setPaper('A4', 'portrait');

        // Renderiza el PDF
        $dompdf->render();

        // Devuelve el objeto creado a partir de la instancia
        return $dompdf;
    }
}
