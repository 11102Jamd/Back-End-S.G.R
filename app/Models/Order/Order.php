<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Order\OrderDetail;



class Order extends Model
{
    

    protected $table = 'order';
    protected $primaryKey = 'ID_order';

    protected $fillable = [
        'ID_user',
        'orderDdate',
        'orderTotal',
        
    ];

    protected $casts = [
        'orderDate' => 'datetime',
        'orderTotal' => 'decimal:2',
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
            'orderDate' => 'required|date|before_or_equal:today',
            'orderTotal' => 'required|numeric|min:0.01|max:999999.99',
            
        ];
    }

    // Scope para pedidos recientes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('orderDate', '>=', now()->subDays($days));
    }

    // Scope para filtrar por estado
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Accessor para fecha formateada
    public function getFormattedDateAttribute()
    {
        return $this->orderDate ? $this->orderDate->format('d/m/Y H:i') : null;
    }

    // Mutator para redondear el total
    public function setOrderTotalAttribute($value)
    {
        $this->attributes['orderTotal'] = round($value, 2);
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
            if (empty($order->orderDate)) {
                $order->orderDate = now();
            }
        });

        static::deleting(function ($order) {
            $order->orderDetails()->delete();
        });
    }
}