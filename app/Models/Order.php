<?php

namespace App\Models;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'order';
    
    protected $fillable = [
        'supplier_name',
        'order_date',
        'order_total'
    ];

    //Relacion de las tablas pivote
    public function batches(): HasMany
    {
        return $this->hasMany(InputBatch::class, 'order_id', 'id');
    }
}
