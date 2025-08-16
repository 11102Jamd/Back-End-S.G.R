<?php

namespace App\Models\Order;



use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Fabricacion\Manufacturing;
use App\Models\Order\OrderDetail;

/*creo la clase Order que extiende de Model y
defino los campos que se pueden llenar masivamente*/
class Product extends Model
{
    protected $table = 'product';

    protected $fillable = [
        'ProductName',      
        'InitialQuantity',  
        'CurrentStock',     
        'UnityPrice',        
    ];

    // Hago al relacion con Manufacturing (Fabricacion) de UNO a MUCHOS
    public function manufacturing(): HasMany
    {
        return $this->hasMany(Manufacturing::class);
    }

    // Hago al relacion con OrderDetail (DetallePedido) de uno a muchos
    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    // Este accesior devuelve el stock disponible restando la cantidad de pedidos
    public function getAvailableStockAttribute()
    {
        return $this->CurrentStock - $this->orderDetails->sum('quantity');
    }
}