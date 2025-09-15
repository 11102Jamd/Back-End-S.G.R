<?php

/**
 * conotrolador para la generacion de PDF de compras
 *
 * Este controlador encapsula la logica de exportacion de un PDF
 * que contiene todo el historial de las compras Realizadas
 *
 * @author Juan Alejandro Muñoz Devia
 */

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderPdfController extends Controller
{
    /**
     * Definimos una propiedad que alamcena el objeto del Servicio
     */
    protected $pdfService;

    /**
     * Inyecta el objeto del servicio PdfService.
     *
     * Este método se encarga de recibir e inyectar el objeto de PdfService
     * dentro del controlador OrderPdfController, para poder generar y personalizar
     * documentos PDF según los parámetros que se requieran.
     *
     * @param \App\Services\PdfService $pdfService Instancia del servicio de PDF.
     * @return void
     */
    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Exporta el PDF utilizando las solicitudes http
     *
     * Este metodo valida las solicitudes de la informacion que recibe
     * la cual es la fecha de inicion y la fecha final, utilizando el metodo
     * del la clase pdfService, se le asignas los valores que el pdf debe incluir
     * para que este sea exportado
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP con las fechas de inicio y fin.
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse Respuesta PDF si es exitoso, o JSON con errores.
     * @throws \Illuminate\Validation\ValidationException Si las fechas no cumplen las reglas de validación.
     */
    public function exportPdf(Request $request)
    {
        set_time_limit(300); // 5 minutos máximo

        try {
            //solicitud de fechas
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);

            /**
             *
             * tomas las fechas de la solicitud y las convierten en instancias de carbon
             * para ajustar la fecha de inicio en 00:00:00 y la fecha final a la ultima hora del dia
             * y es util porque permite generar las consultas del dia completo
             */
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

            // Consulta optimizada
            $orders = Order::with(['batches.input'])
                ->whereBetween('order_date', [$startDate, $endDate])
                ->orderBy('order_date', 'asc')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'error' => 'No hay compras en el rango de fechas establecido'
                ], 404);
            }

            //suma el total de todas las compras realizadas
            $totalOrders = $orders->sum('order_total');

            //data almacena el conjunto de variables que debe tener la vista blade
            $data = [
                'orders' => $orders,
                'totalOrders' => $totalOrders,
                'startDate' => $startDate->format('d/m/Y'),
                'endDate' => $endDate->format('d/m/Y'),
                'generateAt' => now()->format('d/m/Y')
            ];

            /**
             * 
             * utiliza una variable que llama al servicio y utiliza el metodo
             * que general el pdf de acuerdo a la vista blade
             */
            $pdf = $this->pdfService->generatePdf('pdf.orders', $data);

            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="reporte-compras.pdf"');
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error en las reglas de validacion.',
                'details' => $e->getMessage()
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Error al generar PDF:', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error interno al generar el PDF.',
                'details' => env('APP_DEBUG') ? $th->getMessage() : null,
            ], 500);
        }
    }
}
