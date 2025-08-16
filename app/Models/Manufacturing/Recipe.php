<?php

namespace App\Models\Manufacturing;

use App\Models\Manufacturing\Manufacturing;
use App\Models\PurchaseOrders\Inputs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipe extends Model
{
    protected $table = 'recipe';

    protected $fillable = [
        'ID_manufacturing',
        'ID_inputs',
        'AmountSpent',
        'UnitMeasurement',
        'PriceQuantitySpent',
    ];

    protected $attributes = [
        'PriceQuantitySpent' => 0,
        'UnitMeasurement' => 'g',
    ];

    public function Manufacturing()
    {
        return $this->belongsTo(Manufacturing::class, 'ID_manufacturing');
    }

    public function Input(): BelongsTo
    {
        return $this->belongsTo(Inputs::class, 'ID_inputs');
    }

    protected static function booted()
    {
        static::saving(function ($recipe) {
            $recipe->PriceQuantitySpent = $recipe->calculatePriceSpent();
        });
    }

    private function getGramsAttribute(float $amountGrams, string $orderUnit): float
    {
        switch ($orderUnit) {
            case 'kg':
                return $amountGrams / 1000;
            case 'lb':
                return $amountGrams / 453.592;
            case 'l':
                return $amountGrams / 1000; //  densidad del agua
            default:
                return $amountGrams;
        }
    }



    private function getPriceReference()
    {
        return $this->Input->inputOrders()->with(['purchaseOrder' => function ($query) {
            $query->orderBy('PurchaseOrderDate', 'desc');
        }])->first();
    }

    public function calculatePriceSpent(): float
    {
        $input = $this->Input;
        if (!$input) {
            throw new \Exception("El insumo asociado no existe.");
        }
        $inputOrder = $this->getPriceReference();

        if (!$inputOrder) {
            throw new \Exception("El insumo no esta abastecido");
        }

        $amountManufacturing = $this->getGramsAttribute($this->AmountSpent, $inputOrder->UnitMeasurement);

        $UnityPrice = $inputOrder->UnityPrice;
        return round($amountManufacturing * $UnityPrice , 2);
    }

    public function restoreStockInputs(): void
    {
        $input = $this->Input;

        if ($input) {
            $input->increment('CurrentStock', $this->AmountSpent);
        }
    }
    
}
