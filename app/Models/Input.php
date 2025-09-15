<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\InputBatch;
use App\Models\ProductionConsumption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Input extends Model
{
    use HasFactory, SoftDeletes;

    //Nombre de la tabla asociada a la bd
    protected $table = 'input';

    //Campos para asignar en los insumos
    protected $fillable = [
        'name',
        'category'
    ];

    protected $dates = ['deleted_at'];

    //Metodo para limitar la eliminacion, una receta no queda huerfana


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
    /**
     * Mettodo que filtyara
     */
    public function  oldestActiveBatch()
    {
        return $this->batches()->where('quantity_remaining', '>', 0)->orderBy('created_at', 'asc')->first();
    }
}
