<?php

namespace App\Services;

use App\Models\InputBatch;
use App\Models\Input;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use function Symfony\Component\Clock\now;

/**
 * Servicio encargado de la gestión de órdenes de compra y lotes de insumos.
 *
 * Esta clase maneja la creación de órdenes, cálculo de totales,
 * conversión de unidades a gramos y registro de lotes asociados.
 *
 * @package App\Services
 */
class OrderService
{
    /**
     * Obtiene el siguiente número de lote para un insumo específico.
     *
     * Si el insumo ya tiene lotes registrados, incrementa el último número;
     * de lo contrario, comienza desde 1.
     *
     * @param  int  $inputId  ID del insumo.
     * @return int  Número de lote siguiente.
     */
    protected function getNextBatchNumber(int $inputId): int
    {
        $lastBatch = InputBatch::where('input_id', $inputId)->max('batch_number');

        return $lastBatch ? $lastBatch + 1 : 1;
    }

    /**
     * Convierte una cantidad dada a gramos según su unidad de medida.
     *
     * @param  float  $quantity  Cantidad a convertir.
     * @param  string $unit  Unidad de medida (kg, lb, l, un).
     * @return float  Cantidad equivalente en gramos.
     */
    protected function convertToGrams(float $quantity, string $unit): float
    {
        $conversions = [
            'kg' => 1000,
            'lb' => 453.592,
            'l'  => 1000,
            'un' => 1
        ];

        $unit = strtolower($unit);

        return $quantity * ($conversions[$unit] ?? 1);
    }

    /**
     * Crea una nueva orden de compra con sus lotes asociados.
     *
     * El proceso se ejecuta dentro de una transacción:
     * - Calcula el total de la orden.
     * - Crea la orden en la base de datos.
     * - Registra los lotes asociados a cada insumo.
     *
     * @param  array  $orderData  Datos de la orden y sus items.
     * @return \App\Models\Order  Orden creada con lotes relacionados.
     */
    public function createOrderWithBatches(array $orderData): Order
    {
        return DB::transaction(function () use ($orderData) {
            // Calcular el total de la orden
            $orderTotal = collect($orderData['items'])->sum(function ($item) {
                return round($item['quantity_total'] * $item['unit_price'], 3);
            });

            // Crear la orden
            $order = Order::create([
                'supplier_name' => $orderData['supplier_name'],
                'order_date'    => $orderData['order_date'],
                'order_total'   => $orderTotal
            ]);

            // IDs de insumos
            $inputIds = collect($orderData['items'])->pluck('input_id');

            // Inputs precargados
            $inputs = Input::whereIn('id', $inputIds)->get()->keyBy('id');

            // Últimos lotes por insumo
            $lastBatchNumbers = InputBatch::whereIn('input_id', $inputIds)
                ->select('input_id', DB::raw('MAX(batch_number) as max_batch'))
                ->groupBy('input_id')
                ->pluck('max_batch', 'input_id');

            // Crear los lotes asociados
            foreach ($orderData['items'] as $item) {
                $this->createInputBatch($order->id, $item, $inputs, $lastBatchNumbers);
            }

            return $order->load('batches');
        });
    }

    /**
     * Registra un nuevo lote de insumo dentro de una orden de compra.
     *
     * - Convierte las cantidades a gramos.
     * - Calcula el número de lote correspondiente.
     * - Guarda el lote en la base de datos.
     *
     * @param  int  $orderId  ID de la orden asociada.
     * @param  array  $itemData  Datos del insumo (id, cantidad, precio, etc.).
     * @param  \Illuminate\Support\Collection  $inputs  Colección de insumos precargados.
     * @param  \Illuminate\Support\Collection  $lastBatchNumbers  Últimos lotes por insumo.
     * @return \App\Models\InputBatch  Lote creado.
     */
    protected function createInputBatch(
        int $orderId,
        array $itemData,
        Collection $inputs,
        Collection $lastBatchNumbers
    ): InputBatch {
        // Obtener el insumo desde la colección
        $input = $inputs[$itemData['input_id']];

        // Estandarizar cantidad a gramos
        $quantityInGrams = $this->convertToGrams(
            $itemData['quantity_total'],
            $input->unit
        );

        // Determinar el número de lote
        $batchNumber = isset($lastBatchNumbers[$itemData['input_id']])
            ? $lastBatchNumbers[$itemData['input_id']] + 1
            : 1;

        // Crear lote
        return InputBatch::create([
            'order_id' => $orderId,
            'input_id' => $itemData['input_id'],
            'quantity_total' => round($itemData['quantity_total'], 3),
            'quantity_remaining' => $quantityInGrams,
            'unit_price' => round($itemData['unit_price'], 3),
            'subtotal_price' => round($itemData['quantity_total'] * $itemData['unit_price'], 3),
            'batch_number' => $batchNumber,
            'received_date' => now(),
        ]);
    }
}
