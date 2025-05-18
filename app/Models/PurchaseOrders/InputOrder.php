<?php

namespace App\Models\PurchaseOrders;

use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrders\Inputs;
use App\Models\PurchaseOrders\PurchaseOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InputOrder extends Model
{
    //
    protected $table = 'input_order';

    protected $fillable = [
        'ID_purchase_order',
        'ID_input',
        'PriceQuantity'
    ];

    //Creamos los metodos para relacionar las tablas a trabajar con su respectivo ID
    public function input():BelongsTo
    {
        return $this->belongsTo(Inputs::class,'ID_input');
    }

    //Metodo con el ID de la tabla pivote
    public function purchaseOrder():BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class,'ID_purchase_order');
    }
}
