<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\InputBatch;
use App\Models\ProductionConsumption;

class Input extends Model
{
    //Nombre de la tabla asociada a la bd
    protected $table = 'input';
    
    //Campos para asignar en los insumos
    protected $fillable = [
        'name',
        'unit'
    ];

    //Metodo para limitar la eliminacion, una receta no queda huerfana
    protected static function booted()
    {
        //Valida el insert para que no se generen resgistros inecesarios a la bd, es como un filtro para hacer los inserts
        static::saving(function ($input) {
            if (!in_array(strtolower($input->unit), ['kg', 'g', 'lb', 'l', 'un'])) {
                throw new \Exception("Unidad no valida para guardar cambios");
            }
        });
        //Antes de confirmar el eliminado lanza la excepcion y bloquea el delete si no cumple con los parametros
        static::deleting(function ($input) {
            if ($input->producs()->count() > 0) {
                throw new \Exception("No puedes eliminar un insumo asociado a una receta");
            }
        });
    }

    //Relacion de insumos con los lotes,un insumo puede tener muchos lotes
    public function batches(): HasMany
    {
        return $this->hasMany(InputBatch::class, 'input_id', 'id');
    }

    //Relacion que tiene con produccion, porque un insumo se puede usar en muchas recetas
    public function productionConsumptions(): HasMany
    {
        return $this->hasMany(ProductionConsumption::class, 'input_id', 'id');
    }

    //Metetodo que filtra los lotes que tienen stock y estan activos, del mas antiguo y disponible.
    public function  oldestActiveBatch()
    {
        return $this->batches()->where('quantity_remaining', '>', 0)->orderBy('create_at', 'asc')->first();
    }
}
