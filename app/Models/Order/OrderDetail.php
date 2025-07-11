<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Order\Order;
use App\Models\Order\Product;

class OrderDetail extends Model{
    
    protected $table = 'orderDetail';

    protected $fillable = [
        'ID_order',
        'ID_product',
        'requestedQuantity',
        'priceQuantity',
    ];

    // Define que OrderDetail pertenece a un Order
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'ID_order');
    }

    // Define kla relacion entre OrderDetail y Product, donde un detalle de pedido tiene productos
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ID_product');
    }

    // Define un accesor para obtener el precio total del detalle de pedido
    public function getTotalPriceAttribute()
    {
        return $this->requestedQuantity * $this->princeQuantity;
    }  

    // Mutator para redondear el precio a dos decimales
    public function setPriceQuantityAttribute($value)
    {
        $this->attributes['princeQuantity'] = round($value, 2);
    }

    // Accessor para obtener el precio unitario formateado
    public function getFormattedPrinceQuantityAttribute()
    {
        return number_format($this->princeQuantity, 2, ',', '.');
    }

    // aqui valido que los datos cumplan con las reglas de la base de datos
    public static function validationRules($id = null)
    {
        return [
            'ID_order' => 'required|exists:orders,id',
            'ID_product' => 'required|exists:product,id',
            'requestedQuantity' => 'required|numeric|min:0',
            'princeQuantity' => 'required|numeric|min:0.01',
        ];
    }
}