<?php

namespace App\Services;

use App\Models\InputBatch;
use App\Models\Input;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

use function Symfony\Component\Clock\now;

class  OrderService
{

    //Retorna el siguiente numero del lote para un insumo, si existe añade 1 y no existe inicia en 1
    protected function getNextBatchNumber(int $inputId)
    {
        $lastBatch  =  InputBatch::where('input_id', $inputId)->orderBy('batch_number')->first();
        return $lastBatch ? $lastBatch->batch_number + 1 : 1;
    }

    //Metodo protegido que convierte una cantidad a gramos segun la unidad de medida
    protected function convertToGrams(float $quantity, string $unit)
    {
        return  match (strtolower($unit)) {
            'kg' => $quantity  * 1000,
            'lb' => $quantity * 453.592,
            'l' => $quantity * 1000,
            'un' => $quantity * 1,
            default => $quantity
        };
    }

    //Metodo que crea ordenes de compra, calcula el total y guarda y registra los lotes 
    public function createOrderWithBatches(array $orderData): Order
    {

        //Iniciamos una transaccion con la bd para guardar los datos
        return DB::transaction(function () use ($orderData) {
            
            //Calcular el total de la orden de compra redondeanda a tres decimales
            $orderTotal = collect($orderData['items'])->sum(function ($item) {
                return round($item['quantity_total'] * $item['unit_price'], 3);
            });

            //Crea la orden de la bd con sus respectivos campos
            $order = Order::create([
                'supplier_name' => $orderData['supplier_name'],
                'order_date' => $orderData['order_date'],
                'order_total' => $orderTotal
            ]);

            //Crea los lotes asociados a la orden por el id
            foreach ($orderData['items'] as $item) {
                $this->createInputBatch($order->id, $item);
            }

            //Retorna la orden con la relacion cargada a los lotes
            return $order->load('batches');
        });
    }

    /*Metodo protegido que  se encarga de registrar un nuevo lote de insumo dentro de orden de compra,
    al que se le pasan dos parametros importantes, 
    el id de la compra a la que pertenece el lote, un array con los datos del insumo
    */
    protected function createInputBatch(int $orderId, array $itemData): InputBatch
    {
        //Busca el insumo por el id
        $input = Input::findOrFail($itemData['input_id']);
        //llamamos al metodo para hacer la conversion a gramos, estandarizacion para guardar los datos
        $quantityInGrams = $this->convertToGrams($itemData['quantity_total'], $input->unit);
        //Crea un nuevo lote en la bd
        return InputBatch::create([
            'order_id' => $orderId,
            'input_id' => $itemData['input_id'],
            'quantity_total' => round($itemData['quantity_total'], 3),
            'quantity_remaining' =>  $quantityInGrams,
            'unit_price' => round($itemData['unit_price'], 3),
            'subtotal_price' => round($itemData['quantity_total'] * $itemData['unit_price'], 3),
            'batch_number' => $this->getNextBatchNumber($itemData['input_id']),
            'received_date' => now()
        ]);
    }
}
