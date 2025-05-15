<?php

namespace App\Models\Fabricacion;

use App\Models\Manufacturing;
use App\Models\PurchaseOrders\Inputs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Recipes extends Model
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

    public function getGramsAttribute(): float
    {
        switch (strtolower($this->UnitMeasurement)) {
            case 'kg':
                return $this->AmountSpent * 1000;
            case 'lb':
                return $this->AmountSpent / 453.592;
            case 'l':
                return $this->AmountSpent * 1000; //  densidad del agua
            default:
                return $this->AmountSpent;
        }
    }

    public function calculatePriceSpent(): float
    {
        $input = $this->Input;
        if (!$input) {
            throw new \Exception("El insumo asociado no existe.");
        }
        // Obtener el primer inputOrder relacionado si existe, sino fallback al insumo
        $inputOrder = $input->inputOrders()->first();
        $unitPrice = $inputOrder->UnityPrice ?? $input->Price;
        $unitQuantity = $inputOrder->UnitQuantity ?? $input->UnitQuantity ?? 1;
        if ($unitQuantity <= 0) {
            throw new \Exception("La cantidad unitaria debe ser mayor que cero.");
        }
        $pricePerGram = $unitPrice / $unitQuantity;
        $totalPrice = $this->grams * $pricePerGram;
        return round($totalPrice, 2);
    }
    public function restoreStockInputs(): void
    {
        $input = $this->Input;

        if ($input) {
            $input->increment('CurrentStock', $this->AmountSpent);
        }
    }
}
