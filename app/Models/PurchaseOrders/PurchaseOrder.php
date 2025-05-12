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

    //Metodo que recibe un arreglo que contiene  los insumos
    public function InputsReceived(array $inputs)
    {

        $items = [];
        $total = 0;

        foreach ($inputs as $items) {
            try {
                $input = Inputs::findOrFail($items['ID_Input']);
                $grams = $input->ConvertUnit($items['UnitMeasurement'], $items['InitialQuantity']);
                $subTotal = $items['InitialQuantity'] * $items['UnityPrice'];
                $input->increment('CurrentStock', $grams);
                $inputOrder = $this->InputOrders()->create([
                    'ID_input' => $input->id,
                    'PriceQuantity' => $subTotal
                ]);
                $total += $subTotal;
                $items[]=$inputOrder;

            } catch (\Throwable $th) {
                //throw $th;
                return $th->getMessage();
            }
        }
        $this->PurchaseTotal=$total;
        $this->save();
        return response()->json([
            'Order'=> PurchaseOrder::with('InputOrders'),
            'message'=>'Datos Guardados correctamente',
        ],202);
    }
}
