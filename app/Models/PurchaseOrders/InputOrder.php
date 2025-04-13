<?php

namespace App\Models\PurchaseOrders;

use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrders\Inputs;
use App\Models\PurchaseOrders\PurchaseOrder;

class InputOrder extends Model
{
    //
    protected $table = 'input_order';

    protected $fillable = [
        'ID_purchase_order',
        'ID_input',
        'PriceQuantity'
    ];

    public function Input()
    {
        return $this->belongsTo(Inputs::class);
    }

    public function PurchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
