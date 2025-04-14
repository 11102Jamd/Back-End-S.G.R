<?php

namespace App\Models\PurchaseOrders;

use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrders\Supplier;
use App\Models\PurchaseOrders\InputOrder;

class PurchaseOrder extends Model
{
    //
    protected $table = 'purchase_order';

    protected $fillable = [
        'PurchaseOrderDate',
        'PurchaseTotal'
    ];

    public function Supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function InputOrders()
    {
        return $this->hasMany(InputOrder::class);
    }
}
