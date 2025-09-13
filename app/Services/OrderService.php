<?php

namespace App\Services;

use App\Models\InputBatch;
use App\Models\Input;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use function Symfony\Component\Clock\now;

class  OrderService
{

    //Retorna el siguiente numero del lote para un insumo, si existe añade 1 y no existe inicia en 1
    protected function getNextBatchNumber(int $inputId)
    {
        // $lastBatch  =  InputBatch::where('input_id', $inputId)->orderBy('batch_number')->first();
        $lastBatch  =  InputBatch::where('input_id', $inputId)->max('batch_number');
        // return $lastBatch ? $lastBatch->batch_number + 1 : 1;
        return $lastBatch ? $lastBatch + 1 : 1;
    }

    //Metodo protegido que convierte una cantidad a gramos segun la unidad de medida
    protected function convertToGrams(float $quantity, string $unit): float
    {
        // return  match (strtolower($unit)) {
        //     'kg' => $quantity  * 1000,
        //     'lb' => $quantity * 453.592,
        //     'l' => $quantity * 1000,
        //     'un' => $quantity * 1,
        //     default => $quantity
        // };

        $conversions = [
            'kg' => 1000,
            'lb' => 453.592,
            'l' => 1000,
            'un' => 1
        ];

        $unit = strtolower($unit);
        return $quantity * ($conversions[$unit] ?? 1);
    }

    //Metodo que crea ordenes de compra, calcula el total y guarda y registra los lotes

    public function createOrderWithBatches(array $orderData): Order
    {
        return DB::transaction(function () use ($orderData) {

            // Calcular el total de la orden de compra redondeado a tres decimales
            $orderTotal = collect($orderData['items'])->sum(function ($item) {
                return round($item['quantity_total'] * $item['unit_price'], 3);
            });

            // Crear la orden en la BD
            $order = Order::create([
                'supplier_name' => $orderData['supplier_name'],
                'order_date'    => $orderData['order_date'],
                'order_total'   => $orderTotal
            ]);

            // Sacar todos los input_ids de los items
            $inputIds = collect($orderData['items'])->pluck('input_id');

            // 1. Obtener todos los Inputs de una sola vez
            $inputs = Input::whereIn('id', $inputIds)->get()->keyBy('id');

            // 2. Obtener el último batch_number de cada input
            $lastBatchNumbers = InputBatch::whereIn('input_id', $inputIds)
                ->select('input_id', DB::raw('MAX(batch_number) as max_batch'))
                ->groupBy('input_id')
                ->pluck('max_batch', 'input_id');

            // 3. Crear los lotes optimizados
            foreach ($orderData['items'] as $item) {
                $this->createInputBatch($order->id, $item, $inputs, $lastBatchNumbers);
            }

            // Retornar la orden con las relaciones cargadas
            return $order->load('batches');
        });
    }


    /*Metodo protegido que  se encarga de registrar un nuevo lote de insumo dentro de orden de compra,
    al que se le pasan dos parametros importantes,
    el id de la compra a la que pertenece el lote, un array con los datos del insumo
    */

    /**
     * Me
     */
    protected function createInputBatch(int $orderId, array $itemData, Collection $inputs, Collection $lastBatchNumbers): InputBatch
    {
        // Obtener el Input desde la colección cargada (sin query a la BD)
        $input = $inputs[$itemData['input_id']];

        // Calcular cantidad en gramos (estandarización)
        $quantityInGrams = $this->convertToGrams($itemData['quantity_total'], $input->unit);

        // Calcular el número de lote usando los datos precargados
        $batchNumber = isset($lastBatchNumbers[$itemData['input_id']]) ? $lastBatchNumbers[$itemData['input_id']] + 1 : 1;

        // Crear el lote en la BD
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
