<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Order\OrderDetail;



class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'order_date',
        'order_total',
        'status'
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'order_total' => 'decimal:2',
    ];

    // Relación con detalles de pedido (ajustado a nombre de tabla 'orderDetail')
    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'ID_order');
    }

    // Relación con usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    
    // Scope para pedidos recientes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('order_date', '>=', now()->subDays($days));
    }

    // Accesor para fecha formateada
    public function getFormattedDateAttribute()
    {
        return $this->order_date?->format('d/m/Y H:i');
    }

    // Mutador para asegurar formato correcto del total
    public function setOrderTotalAttribute($value)
    {
        $this->attributes['order_total'] = round($value, 2);
    }

    // Calcular total basado en detalles
    public function calculateTotal()
    {
        return $this->details->sum(function($detail) {
            return $detail->requestedQuantity * $detail->princeQuantity;
        });
    }

    // Actualizar el total del pedido
    public function refreshTotal()
    {
        $this->update(['order_total' => $this->calculateTotal()]);
        return $this;
    }

    // Eventos del modelo
    protected static function booted()
    {
        // Establecer fecha automáticamente al crear
        static::creating(function ($order) {
            if (empty($order->order_date)) {
                $order->order_date = now();
            }
        });

        // Eliminar detalles al eliminar pedido
        static::deleting(function ($order) {
            $order->details()->delete();
        });
    }
}