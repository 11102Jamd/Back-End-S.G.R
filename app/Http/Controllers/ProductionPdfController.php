<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Services\PdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductionPdfController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function exportPdf(Request $request)
    {
        try {

            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

            $productions = Production::with(['productionConsumptions.batch.input', 'productProduction.product', 'recipe'])
                ->whereBetween('production_date', [$startDate, $endDate])
                ->orderBy('production_date', 'asc')
                ->get();

            if ($productions->isEmpty()) {
                return response()->json([
                    'error' => 'No hay producciones en el rango de fechas establecido'
                ], 404);
            }

            $totalProductions = $productions->sum('total_cost');

            $data = [
                'productions' => $productions,
                'totalProductions' => $totalProductions,
                'startDate' => $startDate->format('d/m/Y'),
                'endDate' => $endDate->format('d/m/Y'),
                'generateAt' => now()->format('d/m/Y')
            ];

            $pdf = $this->pdfService->generatePdf('pdf.productions', $data);

            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="reporte-productions.pdf"')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error en las reglas de validacion.',
                'details' => $e->getMessage()
            ], 422);
        } catch (\Throwable $th) {
            Log::error('Error al generar PDF:', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error interno al generar el PDF.',
                'details' => env('APP_DEBUG') ? $th->getMessage() : null,
            ], 500);
        }
    }
}
