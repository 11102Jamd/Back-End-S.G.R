<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Order\OrderDetail;



class Order extends Model
{
    use SoftDeletes;

    protected $table = 'order';
    protected $primaryKey = 'ID_order';

    protected $fillable = [
        'ID_user',
        'order_date',
        'order_total',
        // agrega aquí otros campos si los tienes, como 'status'
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'order_total' => 'decimal:2',
    ];

    // Relación: Un pedido tiene muchos detalles
    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'ID_order', 'ID_order');
    }

    // Relación: Un pedido pertenece a un usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ID_user', 'ID_user');
    }

    // Validación de los datos del pedido
    public static function validationRules($id = null): array
    {
        return [
            'ID_user' => 'required|exists:users,ID_user',
            'order_date' => 'required|date|before_or_equal:today',
            'order_total' => 'required|numeric|min:0.01|max:999999.99',
            // 'status' => 'nullable|in:pending,processing,completed,cancelled', // si tienes este campo
        ];
    }

    // Scope para pedidos recientes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('order_date', '>=', now()->subDays($days));
    }

    // Scope para filtrar por estado
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Accessor para fecha formateada
    public function getFormattedDateAttribute()
    {
        return $this->order_date ? $this->order_date->format('d/m/Y H:i') : null;
    }

    // Mutator para redondear el total
    public function setOrderTotalAttribute($value)
    {
        $this->attributes['order_total'] = round($value, 2);
    }

    // Calcula el total sumando los detalles
    public function calculateTotal()
    {
        return $this->orderDetails->sum(function($detail) {
            return $detail->Requestedquantity * $detail->PrinceQuantity;
        });
    }

    // Eventos de modelo
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->order_date)) {
                $order->order_date = now();
            }
        });

        static::deleting(function ($order) {
            $order->orderDetails()->delete();
        });
    }
}