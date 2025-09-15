<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
Use App\Models\Sale;
Use App\Models\Product;

//creo la clase SaleProduct que extiende de Model
class SaleProduct extends Model
{
    protected $table = 'sale_product';

    protected $fillable =
    [
        'sale_id',
        'product_id',
        'quantity_requested',
        'subtotal_price',
    ];

    // Definir las relaciones con otros modelos
    Public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    Public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

}
