<?php

namespace App\Models\PurchaseOrders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


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
        'InitialQuantity' =>0,
        'UnitMeasurement' =>'g',
        'CurrentStock' =>0,
        'UnitMeasurementGrams' =>'g',
        'UnityPrice'=>0
    ];

    public function InputOrders()
    {
        return $this->hasMany(InputOrder::class);
    }


    //Metodo que convierte la unidad de medida.
    /*public function ConvertUnit($unit,$quantity){
        
        //Validar los datos ingresados.
        $unit=strtolower($unit);
        $quantity=null;

        if (!is_numeric($quantity)) {
            throw new \InvalidArgumentException('El valor debe ser numérico');
        }

        try {
            switch ($unit) {
                case 'Kg':
                    return  $quantity * 1000;
                case 'Lb'; 
                    return $quantity * 453.593;         
                default:
                    return $unit;
            }
    
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Unidad No identidicada, Por favor Digite Kg o Lb, unidades de medida validas.',
                'message' => $th->getMessage(),
            ],422);
        }
    }*/

    //Metodo para convertir a gramos
    public static function convertGrams(string $unit, float $quantity): float
    {
        $unit = strtolower($unit);

        /*
        *Condicional que verifica que sea de tipo entero
        *pasando el valor mediante la variable como parametro
        *Se implementa una excepcion en caso de que ingresen una cadena de caracteres.
        */
        if (!is_numeric($quantity)) {
            throw new \InvalidArgumentException('El valor debe ser númerico');
        }

        /*
        */
        return match ($unit) {
            'Kg' => $quantity * 1000,
            'Lb' => $quantity * 453.592,
            default => throw new \InvalidArgumentException("Unidad no válida. Usea 'Kg' o 'Lb'."),
        };
    }

    //Metodo para agregar la entradas
    public function registerInputs(array $entradas)
    {
        DB::beginTransaction();

        try {
            foreach ($entradas as $entrada) {
                $input = Inputs::findOrFail($entrada['input_id']);

                // Convertir a gramos usando el método estático
                $gramos = Inputs::convertToGrams($entrada['unidad'], $entrada['cantidad']);

                // Aumentar el stock usando el método del modelo
                $input->aumentarStock($gramos);
            }

            DB::commit();

            return response()->json(['message' => 'Entradas registradas correctamente.']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Hubo un problema al registrar las entradas.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

//objeto $inputs = new Inputs();