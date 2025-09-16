<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Production;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductProduction extends Model
{
    use HasFactory;

    protected $table = 'product_production';

    protected $fillable = [
        'production_id',
        'product_id',
        'quantity_produced',
        'profit_margin_porcentage'
    ];

    protected $attributes = [
        'quantity_produced' => 0,
        'profit_margin_porcentage' => 0
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class, 'production_id');
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'sale_product', 'product_id', 'sale_id')
                    ->withPivot('quantity_requested', 'subtotal_price')
                    ->withTimestamps();
    }
}
