<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Order\OrderDetail;

/*creo la clase Order que extiende de Model y
defino los campos que se pueden llenar masivamente*/
class Order extends Model
{
    protected $table = 'order';

    protected $fillable = [
        'ID_order',
        'ID_user',
        'orderDate',
        'orderTotal'
    ];

    // Hagalo el relacionamiento con detalles del pedido de muchos a uno
    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'ID_order');
    }

    // Hagalo el relacionamiento con detalles del pedido de uno a muchos
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ID_user');
    }

    // Accesor para formatear la fecha
    public function getFormattedDateAttribute()
    {
        return $this->orderDate?->format('d/m/Y H:i');
    }

    // Este método calcula el total del pedido sumando los totales de los detalles
    public function calculateTotal()
    {
        return $this->details->sum(function($detail) {
            return $detail->requestedQuantity * $detail->priceQuantity;
        });
    }
    

    // Actualizar el total del pedido
    public function refreshTotal()
    {
        $this->update(['orderTotal' => $this->calculateTotal()]);
        return $this;
    }


}