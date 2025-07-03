<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Order\Order;
use App\Models\Order\Product;

class OrderDetail extends Model
{
    protected $table = 'detalle_pedido';

    protected $fillable = [
        'ID_Producto',
        'ID_Pedido',
        'precioXUCantidad',
        'cantidad',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'ID_Pedido');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ID_Producto');
    }
}
