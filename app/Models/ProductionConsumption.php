<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionConsumption extends Model
{
    use HasFactory;
    protected $table = 'production_consumptions';

    protected $fillable = [
        'production_id',
        'input_id',
        'input_batches_id',
        'quantity_used',
        'unit_price',
        'total_cost',
    ];

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class, 'production_id');
    }

    public function input(): BelongsTo
    {
        return $this->belongsTo(Input::class, 'input_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InputBatch::class, 'input_batches_id');
    }
}
