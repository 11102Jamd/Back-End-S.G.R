<?php

namespace App\Models\PurchaseOrders;

use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrders\Supplier;
use App\Models\PurchaseOrders\InputOrder;


class PurchaseOrder extends Model
{
    protected $table = 'purchase_order';

    protected $fillable = [
        'ID_supplier',
        'PurchaseOrderDate',
        'PurchaseTotal'
    ];

    protected $attributes = [
        'PurchaseTotal' => 0
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inputOrders()
    {
        //comentariar
        return $this->hasMany(InputOrder::class, 'ID_purchase_order');
    }



    public function addInputs(array $inputs)
    {
        $total = 0;
        $createdInputOrders = [];

        foreach ($inputs as $inputData) {
            $input = Inputs::findOrFail($inputData['ID_input']);

            $grams = $input->convertUnit(
                
                $inputData['UnitMeasurement'],
                $inputData['InitialQuantity']
            );

            $subtotal = $inputData['InitialQuantity'] * $inputData['UnityPrice'];

            $input->increment('CurrentStock', $grams);

            $inputOrder = $this->inputOrders()->create([
                'ID_input' => $input->id,
                'PriceQuantity' => $subtotal,                
                'UnitMeasurement' => $inputData['UnitMeasurement'],
                'InitialQuantity' => $inputData['InitialQuantity'],
                'UnityPrice' => $inputData['UnityPrice']
            ]);            
            $total += $subtotal;
            $createdInputOrders[] = $inputOrder;
        }

        $this->PurchaseTotal = $total;
        $this->save();

        return [
            'order' =>  $this->fresh()->load('inputOrders.input'),
            'input_orders' => $createdInputOrders
        ];
    }
}
