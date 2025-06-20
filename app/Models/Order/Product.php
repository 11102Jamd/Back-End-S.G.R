<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // Nombre exacto de la tabla en la base de datos
    protected $table = 'producto';

    
    protected $fillable = [
        'nombre',        
        'descripcion',    
        'precio',         
        'stockActual',    
        
    ];
}