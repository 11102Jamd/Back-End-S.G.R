<?php

namespace App\Services;

use App\Models\Order;
use App\Models\InputBatch;
use App\Models\Input;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Crea un nuevo lote (batch) para un insumo a partir de los datos de la orden.
     *
     * Este método realiza los siguientes pasos:
     * 1. Obtiene el insumo correspondiente según su ID.
     * 2. Valida que la unidad proporcionada sea compatible con la categoría del insumo.
     * 3. Convierte la cantidad a la unidad estándar según la categoría del insumo.
     * 4. Calcula la cantidad restante, precio unitario, subtotal y número de lote.
     * 5. Crea y devuelve un registro en la tabla de lotes (InputBatch).
     *
     * @param int $orderId el id de la orden de compra
     * @param array $itemData Arreglo con los datos del insumo
     * @return InputBatch  El lote creado con la informacion correspondiente
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el insumo no existe.
     * @throws \Exception Si la unidad no es compatible con la categoría del insumo.
     */
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

    /**
     * Valida la unidad de medida correspondiente a la categoria a lq eu pertenece el insumo
     *
     * categorias:
     * Liquido: recibe L o ml
     * solido_no_con : recibe kg,lb,oz,g
     * solido_con: recibe unicamente un
     *
     * @param string $category la categoria del insumo que toca identificar
     * @param string $unit la unidad a identficar correpondiente a la categoria del insumo
     *
     * @return array ddevuelve un arregklo con la cantidad convertida y su unidad estandar
     * @throws \Exception dispara una excepcion si la unidad o categoria no son validas
     */
    protected function validateUnitForCategory(string $category, string $unit): void
    {
        $unit = strtolower($unit);
        $category = strtolower($category);

        $validUnits = match ($category) {
            'liquido' => ['l', 'ml'],
            'solido_con' => ['un'],
            'solido_no_con' => ['kg', 'g', 'lb', 'oz'],
            default => throw new \Exception("Categoría de insumo no válida: $category")
        };

        if (!in_array($unit, $validUnits)) {
            throw new \Exception("La unidad '$unit' no es válida para la categoría '$category'. Unidades permitidas: " . implode(', ', $validUnits));
        }
    }


    /**
     * conveiirte una cantidad a la unidad estandar segun la categoria del Insumo
     *
     * categorias:
     * Liquido: convierte a ml desde L o ml
     * solido_no_con : convierte a g desde g,kg,lb,oz etc.
     * solido_con : convierte a un (unidad) sin necesidad  de hacver conversion
     *
     * @param float $quantity la canidad a convertir
     * @param string $unit la unidad a envaluar para la conversion
     * @param string $category la categoria del insumo que toca identificar
     *
     * @return array devuelve un arreglo con la cantidad convertida y su unidad estandar
     * @throws \Exception dispara una excepcion si la unidad o categoria no son validas
     */
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

    /**
     * Obtiene el siguente numero del Lote a traves del id del insumo
     *
     * Este método busca el último lote registrado para un insumo específico
     * y devuelve el siguiente número de lote disponible. Si no existe ningún lote
     * previo, retorna 1.
     * @param int $inputId el id del insumo
     * @return int $lastBatch devuelve el sigueinte numerro del lote dispouble
     */
    protected function getNextBatchNumber(int $inputId): int
    {
        $lastBatch = InputBatch::where('input_id', $inputId)
            ->orderByDesc('batch_number')
            ->first();

        return $lastBatch ? $lastBatch->batch_number + 1 : 1;
    }


    /**
     *  Crea una orden junto con sus lotes (batches) asociados.
     *
     * Este método realiza las siguientes acciones:
     * 1. Calcula el total de la orden sumando el subtotal de cada insumo.
     * 2. Crea la orden principal en la base de datos.
     * 3. Crea los lotes correspondientes a cada insumo de la orden usando `createInputBatch`.
     * 4. Retorna la orden con sus lotes cargados.
     *
     * Todas las operaciones se ejecutan dentro de una transacción para garantizar
     * consistencia en caso de errores.
     *
     * @param array $orderData Arreglo con los datos de la compra
     *
     *  @return Order La orden creada con sus lotes relacionados cargados.
     *
     * @throws \Exception Si ocurre un error durante la creación de la orden o lotes,
     *                    la transacción será revertida automáticamente.
     */
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
