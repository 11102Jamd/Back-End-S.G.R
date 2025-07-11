<?php

namespace App\Models\Order;

use App\Models\Fabricacion\Manufacturing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Order\OrderDetail;



class Product extends Model
{
    protected $table = 'product';
    protected $primaryKey = 'ID_product'; 

    protected $fillable = [
        'productName',
        'initialQuantity',
        'currentStock',
        'unityPrice',
    ];
    
    
    public function manufacturing(): HasMany
    {
        return $this->hasMany(Manufacturing::class, 'ID_product', 'ID_product');
    }

    // Relación con detalles de pedido
    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'ID_product', 'ID_product');
    }
    
    public function getFormattedPriceAttribute()
    {
        return number_format($this->unityPrice, 2, ',', '.'); 
    }

    //
    public function getCalculatedStockAttribute()
    {
        return $this->initialQuantity - $this->manufacturing->sum('requestedQuantity');
    }

    public static function validationRules($id = null): array
    {

        return [
            'productName' => 'required|string|max:50',
            'initialQuantity' => 'required|integer|min:0',
            'currentStock' => 'sometimes|integer|min:0', 
            'unityPrice' => 'required|numeric|min:0.01',
        ];
    }
}