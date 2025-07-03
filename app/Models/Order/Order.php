<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Order\OrderDetail;



class Order extends Model
{
    protected $table = 'pedido';

    protected $fillable = [
        'Id_usuario',
        'fechaPedido',
        'totalPagar',
    ];

    // One order has many order details
    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'ID_Pedido');
    }

    // One order belongs to one user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Id_usuario');
    }
}
