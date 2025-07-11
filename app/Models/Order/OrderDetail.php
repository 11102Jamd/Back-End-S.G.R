<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Order\Order;
use App\Models\Order\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderDetail extends Model
{
    protected $table = 'orderDetail';
    protected $primaryKey = 'ID_orderDetail';
    protected $fillable = [
        'ID_order',
        'ID_product',
        'requestedQuantity',
        'priceQuantity', 
    ];


    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'ID_order', 'ID_order');
    }


    public function product(): HasMany
    {
        return $this->HasMany(Product::class, 'ID_product', 'ID_product');
    }


    public function getTotalPriceAttribute()
    {
        return $this->requestedQuantity * $this->priceQuantity;
    }  


    public function setPriceQuantityAttribute($value)
    {
        $this->attributes['priceQuantity'] = round($value, 2);
    }

    public function getFormattedPriceQuantityAttribute()
    {
        return number_format($this->priceQuantity, 2, ',', '.');
    }

    public static function validationRules($id = null): array
    {
        return [
            'ID_order' => 'required|exists:order,ID_order',
            'ID_product' => 'required|exists:product,ID_product',
            'requestedQuantity' => 'required|integer|min:1',
            'priceQuantity' => 'required|numeric|min:0.01',
        ];
    }
}