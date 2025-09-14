<?php

namespace App\Services;

use App\Models\Order;
use App\Models\InputBatch;
use App\Models\Input;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected function createInputBatch(int $orderId, array $itemData): InputBatch
    {
        $input = Input::findOrFail($itemData['input_id']);
        $originalUnit = $itemData['unit'];
        $originalQuantity = $itemData['quantity_total'];

        // Validar que la unidad sea compatible con la categoría del insumo
        $this->validateUnitForCategory($input->category, $originalUnit);

        $conversionResult = $this->convertToStandardUnit($originalQuantity, $originalUnit, $input->category);

        return InputBatch::create([
            'input_id' => $itemData['input_id'],
            'order_id' => $orderId,
            'quantity_total' => $originalQuantity,
            'unit' => $originalUnit,
            'quantity_remaining' => round($conversionResult['converted_quantity'], 1),
            'unit_converted' => $conversionResult['standard_unit'],
            'unit_price' => round($itemData['unit_price'], 1),
            'subtotal_price' => round($itemData['quantity_total'] * $itemData['unit_price'], 1),
            'batch_number' => $this->getNextBatchNumber($itemData['input_id']),
            'received_date' => now()
        ]);
    }

    protected function validateUnitForCategory(string $category, string $unit): void
    {
        $unit = strtolower($unit);
        $category = strtolower($category);

        $validUnits = match($category) {
            'liquido' => ['l', 'ml'],
            'solido_con' => ['un'],
            'solido_no_con' => ['kg', 'g', 'lb', 'oz'],
            default => throw new \Exception("Categoría de insumo no válida: $category")
        };

        if (!in_array($unit, $validUnits)) {
            throw new \Exception("La unidad '$unit' no es válida para la categoría '$category'. Unidades permitidas: " . implode(', ', $validUnits));
        }
    }

    protected function convertToStandardUnit(float $quantity, string $unit, string $category): array
    {
        $unit = strtolower($unit);
        $category = strtolower($category);

        switch ($category) {
            case 'liquido':
                // Convertir a mililitros (ml)
                $converted = match ($unit) {
                    'l' => $quantity * 1000,
                    'ml' => $quantity,
                    default => throw new \Exception("Unidad no válida para líquidos: $unit")
                };
                return ['converted_quantity' => $converted, 'standard_unit' => 'ml'];

            case 'solido_no_con':
                // Convertir a gramos (g)
                $converted = match ($unit) {
                    'kg' => $quantity * 1000,
                    'g' => $quantity,
                    'lb' => $quantity * 453.592,
                    'oz' => $quantity * 28.3495,
                    default => throw new \Exception("Unidad no válida para sólidos no conteables: $unit")
                };
                return ['converted_quantity' => $converted, 'standard_unit' => 'g'];

            case 'solido_con':
                // Unidades conteables - no se convierte
                if ($unit !== 'un') {
                    throw new \Exception("Unidad no válida para sólidos conteables: $unit. Solo se permite 'un'");
                }
                return ['converted_quantity' => $quantity, 'standard_unit' => 'un'];

            default:
                throw new \Exception("Categoría de insumo no válida: $category");
        }
    }

    protected function getNextBatchNumber(int $inputId): int
    {
        $lastBatch = InputBatch::where('input_id', $inputId)
            ->orderByDesc('batch_number')
            ->first();

        return $lastBatch ? $lastBatch->batch_number + 1 : 1;
    }

    public function createOrderWithBatches(array $orderData): Order
    {
        return DB::transaction(function () use ($orderData) {
            // Calcular total de la orden
            $orderTotal = collect($orderData['items'])->sum(function ($item) {
                return round($item['quantity_total'] * $item['unit_price'], 1);
            });

            // Crear la orden principal con el total
            $order = Order::create([
                'supplier_name' => $orderData['supplier_name'],
                'order_date' => $orderData['order_date'],
                'order_total' => round($orderTotal, 1)
            ]);

            // Procesar cada item del pedido
            foreach ($orderData['items'] as $item) {
                $this->createInputBatch($order->id, $item);
            }

            return $order->load('batches.input');
        });
    }
}