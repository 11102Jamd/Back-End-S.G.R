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
    protected function getNextBatchNumber(int $inputId)
    {
        $lastBatch  =  InputBatch::where('input_id', $inputId)->orderBy('batch_number')->first();
        return $lastBatch ? $lastBatch->batch_number + 1 : 1;
    }

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

    public function createOrderWithBatches(array $orderData): Order
    {
        return DB::transaction(function () use ($orderData) {
            $orderTotal = collect($orderData['items'])->sum(function ($item) {
                return round($item['quantity_total'] * $item['unit_price'], 3);
            });
            $order = Order::create([
                'supplier_name' => $orderData['supplier_name'],
                'order_date' => $orderData['order_date'],
                'order_total' => $orderTotal
            ]);

            foreach ($orderData['items'] as $item) {
                $this->createInputBatch($order->id, $item);
            }
            return $order->load('batches');
        });
    }

    protected function createInputBatch(int $orderId, array $itemData): InputBatch
    {
        $input = Input::findOrFail($itemData['input_id']);
        $quantityInGrams = $this->convertToGrams($itemData['quantity_total'], $input->unit);
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
