<?php

namespace App\Models\PurchaseOrders;

use App\Models\PurchaseOrders\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
