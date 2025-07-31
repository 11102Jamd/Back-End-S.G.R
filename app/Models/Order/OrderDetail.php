<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderDetail extends Model
{
    protected $table = 'order_details'; 
    protected $primaryKey = 'id'; 

    protected $fillable = [
        'order_id',          
        'product_id',         
        'requested_quantity', 
        'unit_price',         
    ];

    protected $casts = [
        'unit_price' => 'decimal:2', 
    ];

    // Relación con Order (muchos detalles pertenecen a un pedido)
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Relación con Product (muchos detalles pertenecen a un producto)
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Accesor para calcular el precio total
    public function getTotalPriceAttribute()
    {
        return $this->requested_quantity * $this->unit_price;
    }  

    // Mutador para asegurar formato correcto del precio
    public function setUnitPriceAttribute($value)
    {
        $this->attributes['unit_price'] = round($value, 2);
    }

    // Accesor para precio formateado
    public function getFormattedPriceAttribute()
    {
        return number_format($this->unit_price, 2, ',', '.');
    }
}