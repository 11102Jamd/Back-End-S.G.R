<?php

namespace App\Models;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;
    // Importa el trait HasFactory para habilitar la creación de fábricas para este modelo.

    // Define el nombre de la tabla asociada a este modelo.
    protected $table = 'order';


    // Definición de los atributos que se pueden asignar masivamente.
    protected $fillable = [
        'supplier_name',
        'order_date',
        'order_total'
    ];

    //Relacion de las tablas pivote, define una relación uno a muchos con el modelo InputBatch.
    public function batches(): HasMany
    {
        // Retorna la relación, indicando que esta orden está relacionada con múltiples InputBatch
        return $this->hasMany(InputBatch::class, 'order_id', 'id');
    }
}
