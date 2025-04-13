<?php

namespace App\Models\PurchaseOrders;

use App\Models\PurchaseOrders\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    //
    protected $table = 'supplier';

    protected $fillable = [
        'name',
        'email',
        'Addres',
        'Phone'
    ];

    //metodo que devuelve muchos que conecta con el modelo purchaseorder
    public function PurchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
