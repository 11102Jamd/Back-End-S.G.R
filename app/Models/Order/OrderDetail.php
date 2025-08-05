<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;



class OrderDetail extends Model
{
    protected $table = 'orderDetail'; 
    protected $primaryKey = 'id';

    protected $fillable = [
        'ID_order',
        'ID_product',
        'requestedQuantity',
        'princeQuantity'
    ];

    protected $casts = [
        'requestedQuantity' => 'decimal:2',
        'princeQuantity' => 'decimal:3',
    ];

    // Relación con el pedido
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'ID_order');
    }

    // Relación con el producto
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ID_product');
    }

    // Accesor para el precio total del detalle
    public function getTotalPriceAttribute()
    {
        return $this->requestedQuantity * $this->princeQuantity;
    }

    // Eventos del modelo
    protected static function booted()
    {
        // Actualizar el total del pedido cuando cambia un detalle
        static::saved(function ($detail) {
            $detail->order->refreshTotal();
        });

        static::deleted(function ($detail) {
            $detail->order->refreshTotal();
        });
    }
}