<?php

namespace App\Models\PurchaseOrders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\Fabricacion\Recipes;
use App\Models\PurchaseOrders\InputOrder;


class Inputs extends Model
{
    //
    protected $table = 'inputs';

    //Son modificables con datos externos
    protected $fillable = [
        'InputName',
        'InitialQuantity',
        'UnitMeasurement',
        'CurrentStock',
        'UnitMeasurementGrams',
        'UnityPrice'
    ];


    //Si no se pasa un valor se le a=>signa por defecto
    protected $attributes = [
        'CurrentStock' => 0,
        'UnitMeasurementGrams' => 'g',
    ];

    public function inputOrders(): HasMany
    {
        return $this->hasMany(InputOrder::class,'ID_input');
    }

    public function recipes()
    {
        return $this->hasMany(Recipes::class);
    }


    //Metodo que convierte la unidad de medida.
    public function convertUnit($unit, $quantity)
    {
        //Validar los datos ingresados.
        /*if (!is_numeric($quantity)) {
            throw new \InvalidArgumentException('El valor debe ser numérico');
        }*/

        //Verifica la unidad digitada y dependiendo el caso hace la operacion respectiva.
        switch ($unit) {
            case 'kg':
                return $quantity * 1000;
            case 'lb':
                return $quantity * 453.593;
            default:
                return $quantity;
        }
    }
}

//objeto $inputs = new Inputs();