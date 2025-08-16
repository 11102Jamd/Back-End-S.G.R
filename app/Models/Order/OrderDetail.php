<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/*creo la clase OrderDetail que extiende de Model y
defino los campos que se pueden llenar masivamente*/
class OrderDetail extends Model
{
    protected $table = 'orderDetail';

    protected $fillable = [
        'ID_order',
        'ID_product',
        'requestedQuantity',
        'priceQuantity'
    ];

    

    // Relación con el pedido de uno a muchos
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'ID_order');
    }

    // Relación con el producto de uno a muchos
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ID_product');
    }

    // Accesor para el precio total del detalle de cada producto segun la cantidad solicitada
    public function getTotalPriceAttribute()
    {
        return $this->requestedQuantity * $this->priceQuantity;
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