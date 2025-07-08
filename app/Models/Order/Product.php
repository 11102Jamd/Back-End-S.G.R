<?php

namespace App\Models\Order;
use App\Models\Fabricacion\Manufacturing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Order\OrderDetail;


//creo el modelo Product que representa la tabla product en la base de datos
class Product extends Model
{
    protected $table = 'product' ;

    protected $fillable = [
        'ProductName',
        'InitialQuantity',
        'CurrentStock',
        'UnityPrice',
    ];
    
    
    // se define que un producto puede estar en varias fabricaciones y defino su relacion con el modelo Manufacturing

    public function manufacturing(): HasMany
    {
        return $this->hasMany(Manufacturing::class, 'ID_prodcuto');
    }

    // Se define que un producto puede estar en varios pedidos detalles de pedido y defino su relacion con el modelo OrderDetail
    public function orderDetail(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'ID_prodcuto');
    }
    
    // Aqui defino un accesor para obtener el precio formateado del producto

    public function getFormattedPriceAttribute()
    {
        return number_format($this->UnityPrice, 2, ',', '.');
    }

    // Aqui define un accesor para obtener el stock actual del producto

    public function getCurrentStockAttribute()
    {
        return $this->InitialQuantity - $this->manufacturing->sum('Requestedquantity');
    }

    // Aqui validamos que los datos cumplan con la Base de datos

    public static function validationRules($id = null) 
    {

        return [
        'ProductName' => 'required|string|max:50',
        'InitialQuantity' => 'required|integer|min:0',
        'UnityPrice' => 'required|numeric|min:0.01',
        ];
    }
};