<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProductionConsumption;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InputBatch extends Model
{

    // Importa el trait HasFactory para habilitar la creación de fábricas de datos.
    use HasFactory;

    // Define el nombre de la tabla asociada a este modelo. Si no se especifica, Laravel utilizaría el nombre pluralizado del modelo por defecto.
    protected $table = 'input_batches';

    // Definición de los atributos que se pueden asignar masivamente.
    // Esto ayuda a la protección contra la vulnerabilidad de la asignación masiva.
    protected $fillable = [
        'order_id',
        'input_id',
        'quantity_total',
        'quantity_remaining',
        'unit_price',
        'subtotal_price',
        'batch_number',
        'received_date'
    ];

    //Método público llamado input que retorna un tipo de relación esto
    // indica que este modelo está asociado a otro modelo llamado Input.
    public function input(): BelongsTo
    {
        return $this->belongsTo(Input::class, 'input_id');
    }

    //Define una relacion de tipo pertenece a el modelo actual, donde la clave foranea es 'order_id'
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    //Metodo que define la relacion de consumo o gasto de insumos a una produccion
    public function productionConsumptions(): HasMany
    {
        return $this->hasMany(ProductionConsumption::class, 'input_batches_id', 'id');
    }

    //Metodo para utilizar varios lotes si lo requiere el producto que se vaya a preparar
    public function scopeAvaliableForInput($query, $inputId)
    {
        return $this->$query()->where('input_id', $inputId)->where('quantity_remaining', '>', 0)->orderBy('received_date');
    }
}
