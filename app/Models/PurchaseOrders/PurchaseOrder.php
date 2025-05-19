<?php

namespace App\Models\PurchaseOrders;

use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrders\Supplier;
use App\Models\PurchaseOrders\InputOrder;

class PurchaseOrder extends Model
{
    //creo que no es necesario porque leyendo la dc dice que si el nombre es el mismo no hay necesidad de otra vez repetir el nombre eloquent lo añade por defecto
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

    /*el Insumo, su nombre, su cantidad, su unidad de medida, su precio por unidad, el
total x la cantidad y finalmente el total a pagar en la orden de compra.*/

    //Metodo que recibe un arreglo que contiene  los insumos
    public function inputsReceived(array $inputsDates)
    {

        $newOrders = [];
        $total = 0;

        foreach ($inputsDates as $inputDate) {

            //
            $input = Inputs::findOrFail($inputDate['ID_input']);

            //
            $grams = $input->converUnit(
                $inputDate['IinitialQuantity'],
                $inputDate['UnitMeasurement']
            );

            //
            $subTotal = $inputDate['InitialQuantity'] * $inputDate['UnityPrice'];

            //
            $input->increment('currentStock', $grams);

            //
            $inputOrder = $this->inputOrders()->create([
                'ID_input' => $input->id,
                'PriceQuantity' => $subTotal,
                'InitialQuantity' => $inputDate['InitialQuantity'],
                'UnitMeasurement' => $inputDate['UnitMeasurement'],
                'UnityPrice' => $inputDate['UnityPrice']
            ]);

            //
            $total = $subTotal;
            //
            $newOrders[] = $inputOrder;
        }
        $this->PurchaseTotal = $total;
        $this->save();

        return [
            'order' => $this->fresh()->load('inputOrders.input'),
            'input_orders' => $newOrders
        ];
    }
}
