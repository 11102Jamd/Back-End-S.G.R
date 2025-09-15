<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ProductProduction;
use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleProduct;

 
class SaleService 
{
    private function getAvailableStockSafe(int $productId): float
    {
        return ProductProduction::where('product_id', $productId)
            ->sum('quantity_produced');
    }

    private function deductFromProductionStock(int $productId, float $quantityRequested): void
    {
        $productions = ProductProduction::where('product_id', $productId)
            ->where('quantity_produced', '>', 0)
            ->orderBy('created_at', 'asc') // FIFO: Primero en entrar, primero en salir
            ->get();

        $remainingQuantity = $quantityRequested;

        foreach ($productions as $production) {
            if ($remainingQuantity <= 0) break;

            $quantityToDeduct = min($remainingQuantity, $production->quantity_produced);

            // RESTAR físicamente de la producción
            $production->quantity_produced -= $quantityToDeduct;
            $production->save();

            $remainingQuantity -= $quantityToDeduct;
        }

        if ($remainingQuantity > 0) {
            throw new \Exception("Error inesperado: No se pudo deducir todo el stock del producto: {$productId}");
        }
    }

    public function registerSale (array $saleData)

    {

        return  DB::transaction(function() use ($saleData){ 

            try {
                $sale = Sale::create([
                    'user_id' => $saleData['user_id'],
                    'sale_date' => now(),
                    'sale_total' => 0,
                ]) ;

                $totalSale = 0;

                foreach ($saleData['products'] as $productData) {
                    
                    $productId = $productData['product_id'];
                    $quantityRequested = $productData['quantity_requested'];
                    
                    $availableStock = $this->getAvailableStockSafe($productId);
                    
                    if ($availableStock < $quantityRequested) {
                        throw new \Exception("Stock insuficiente para el producto ID: {$productId}. Disponible: {$availableStock}, Requerido: {$quantityRequested}");
                    }

                    $product = Product::find($productId);
                    $subtotal = $product->unit_price * $quantityRequested;
                    $totalSale += $subtotal;

                    SaleProduct::create([
                        'sale_id'=> $sale->id,
                        'product_id' => $productId,
                        'quantity_requested' => $quantityRequested,
                        'subtotal_price' => $subtotal,
                    ]);

                    $this->deductFromProductionStock($productId, $quantityRequested);

                }

                $sale->update(
                    ['sale_total' => $totalSale]
                );

                return [
                    'sale' => $sale->load(['saleProducts', 'user']),
                    'message' => 'Venta registrada exitosamente'
                ];

            } catch (\Throwable $th) {
                DB::rollBack();
                Log::error('Error al registrar venta: ' . $th->getMessage());
                throw new \Exception('Error al procesar la venta: ' . $th->getMessage());
            }
        }) ;
        
    }
}