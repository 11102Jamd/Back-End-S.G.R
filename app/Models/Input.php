<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\InputBatch;
use App\Models\ProductionConsumption;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Input extends Model
{
    use HasFactory;

    protected $table = 'input';
    
    protected $fillable = [
        'name',
        'unit'
    ];

    //Protected static function booted()
    //Metodo para limitar la eliminacio, una receta no queda huerfana
    protected static function booted()
    {
        static::saving(function ($input) {
            if (!in_array(strtolower($input->unit), ['kg', 'g', 'lb', 'l', 'un'])) {
                throw new \Exception("Unidad no valida para guardar cambios");
            }
        });

        static::deleting(function ($input) {
            if ($input->producs()->count() > 0) {
                throw new \Exception("No puedes eliminar un insumo asociado a una receta");
            }
        });
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InputBatch::class, 'input_id', 'id');
    }

    //Relacion  con produyccion
    public function productionConsumptions(): HasMany
    {
        return $this->hasMany(ProductionConsumption::class, 'input_id', 'id');
    }

    //Metetodo que filtra los lotes que tienen stock y estan activos, del mas antiguo.
    public function  oldestActiveBatch()
    {
        return $this->batches()->where('quantity_remaining', '>', 0)->orderBy('created_at', 'asc')->first();
    }
}
