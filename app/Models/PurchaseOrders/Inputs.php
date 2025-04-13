<?php

namespace App\Models\PurchaseOrders;

use Illuminate\Database\Eloquent\Model;

class Inputs extends Model
{
    //
    protected $table = 'inputs';
    protected $fillable = [
        'InputName',
        'InitialQuantity',
        'UnitMeasurement',
        'CurrentStock',
        'UnitMeasurementGrams',
        'UnityPrice'
    ];

    public function InputOrders()
    {
        return $this->hasMany(InputOrder::class);
    }
}
