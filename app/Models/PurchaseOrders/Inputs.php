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
    protected $fillable = [
        'InputName',
        'InitialQuantity',
        'UnitMeasurement',
        'CurrentStock',
        'UnitMeasurementGrams',
        'UnityPrice'
    ];

    protected $attributes = [
        'InitialQuantity' => 0,
        'UnitMeasurement' => 'g',
        'CurrentStock' => 0,
        'UnitMeasurementGrams' => 'g',
        'UnityPrice' => 0
    ];

    public function inputOrders(): HasMany
    {
        return $this->hasMany(InputOrder::class, 'ID_input');
    }

    public function recipes()
    {
        return $this->hasMany(Recipes::class);
    }


    //Metodo que convierte la unidad de medida.
    public function convertUnit($unit, $quantity)
    {

        //pasa las unidades de medida a minusculas.
        $unit = strtolower($unit);

        //Validar los datos ingresados.

        if (!is_numeric($quantity)) {
            throw new \InvalidArgumentException('El valor debe ser numérico');
        }

        //Verifica la unidad digitada y dependiendo el caso hace la operacion respectiva.
        switch ($unit) {
            case 'kg':
                return $quantity * 1000;
            case 'lb':
                return $quantity * 453.593;
            default:
                throw new \InvalidArgumentException('La unidad no puede ser reconocida. Digite la cantidad en "kg" o "lb". ', 1);
        }
    }
}

//objeto $inputs = new Inputs();