<?php

namespace App\Models\Order;

<<<<<<< HEAD
use App\Models\Fabricacion\Manufacturing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

=======


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Fabricacion\Manufacturing;
use App\Models\Order\OrderDetail;

/*creo la clase Order que extiende de Model y
defino los campos que se pueden llenar masivamente*/
>>>>>>> 570757c92e046db4fccce085dc6f73ed4b6e9202
class Product extends Model
{
    protected $table = 'product';

    protected $fillable = [
<<<<<<< HEAD
        'ProductName',
        'InitialQuantity',
        'CurrentStock',
        'UnityPrice'
    ];

    public function manufacturings(): HasMany
=======
        'ProductName',      
        'InitialQuantity',  
        'CurrentStock',     
        'UnityPrice',        
    ];

    // Hago al relacion con Manufacturing (Fabricacion) de UNO a MUCHOS
    public function manufacturing(): HasMany
>>>>>>> 570757c92e046db4fccce085dc6f73ed4b6e9202
    {
        return $this->hasMany(Manufacturing::class);
    }

<<<<<<< HEAD
    // public function orderDetails(): HasMany
    // {
    //     return $this->hasMany()
    // }
}
=======
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
>>>>>>> 570757c92e046db4fccce085dc6f73ed4b6e9202
