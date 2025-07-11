<?php
//
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
        'orderDate',
        'orderTotal',

    ];

    protected $casts = [
        'orderDate' => 'datetime',
        'orderTotal' => 'decimal:2',
    ];

    // Relación con detalles de pedido y productos 
    
    public function orderDetail(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'ID_order', 'ID_order');
    }

    // Relación con usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ID_user', 'ID_user');
    }


    public static function validationRules($id = null): array
    {
        return [
            'ID_user' => 'required|exists:users,ID_user',
            'orderDate' => 'required|date|before_or_equal:today',
            'orderTotal' => 'required|numeric|min:0.01|max:999999.99',

        ];
    }


    public function scopeRecent($query, $days = 30)
    {
        return $query->where('orderDate', '>=', now()->subDays($days));
    }


    public function getFormattedDateAttribute()
    {
        return $this->orderDate ? $this->orderDate->format('d/m/Y H:i') : null;
    }


    public function setOrderTotalAttribute($value)
    {
        $this->attributes['orderTotal'] = round($value, 2);
    }


    public function calculateTotal()
    {
        return $this->orderDetail->sum(function($detail) {
            return $detail->requestedQuantity * $detail->priceQuantity;
        });
    }


    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->orderDate)) {
                $order->orderDate = now();
            }
        });

        static::deleting(function ($order) {
            $order->orderDetail()->delete();
        });
    }
}