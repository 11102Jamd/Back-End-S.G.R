<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProductionConsumption;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InputBatch extends Model
{
    use HasFactory;

    protected $table = 'input_batches';

    protected $fillable = [
        'order_id',
        'input_id',
        'quantity_total',
        'quantity_remaining',
        'unit_price',
        'subtotal_price',
        'batch_number',
        'received_date'
    ];

    //Public function input(): BelongsTo
    public function input(): BelongsTo
    {
        return $this->belongsTo(Input::class, 'input_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // Public function order(): BelongsTo

    public function productionConsumptions(): HasMany
    {
        return $this->hasMany(ProductionConsumption::class, 'input_batches_id', 'id');
    }

    //Metodo para utilizar varios lotes si lo requiere el producto que se vaya a preparar
    public function scopeAvaliableForInput($query, $inputId)
    {
        return $this->$query()->where('input_id', $inputId)->where('quantity_remaining', '>', 0)->orderBy('received_date');
    }
}
