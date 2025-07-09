<?php

namespace App\Models\Manufacturing;

use App\Models\Order\Product;
use App\Models\PurchaseOrders\Inputs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturing extends Model
{
    protected $table = 'manufacturing';

    protected $fillable = [
        'ID_product',
        'ManufacturingTime',
        'Labour',
        'ManufactureProductG',
        'TotalCostProduction',
    ];

    protected $attributes = [
        'Labour' => 0,
        'ManufactureProductG' => 0,
        'TotalCostProduction' => 0,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ID_product');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'ID_manufacturing');
    }

    public function calculateLabour(): self
    {
        if ($this->ManufacturingTime > 0 && $this->ManufacturingTime != 0) {
            $hours = $this->ManufacturingTime / 60;
            $this->Labour = $hours * 10000;
            $this->save();
        }

        return $this;
    }

    public function addIngredients(array $recipes): self//cual es el contexto de self en este caso?
    {
        $total = 0;
        $totalG = 0;

        foreach ($recipes as $data) {
            $input = Inputs::findOrFail($data['ID_inputs']);
            $amount = $data['AmountSpent'];

            if ($input->CurrentStock < $amount) {
                throw new \Exception("Stock insuficiente para {$input->InputName}");
            }

            if (($input->CurrentStock - $amount) <= 200) {
                throw new \Exception("Stock mínimo alcanzado: {$input->InputName}");
            }

            $recipe = $this->recipes()->create([
                'ID_inputs' => $input->id,
                'AmountSpent' => $amount,
                'UnitMeasurement' => 'g',
            ]);

            $subtotal = $recipe->calculatePriceSpent();
            $recipe->update(['PriceQuantitySpent' => $subtotal]);

            $input->decrement('CurrentStock', $amount);

            $total += $subtotal;
            $totalG += $amount;
        }

        $this->TotalCostProduction = $total + $this->labour;
        $this->ManufactureProductG = $totalG;
        $this->save();

        return $this->fresh()->load('recipes.input');
    }
}
