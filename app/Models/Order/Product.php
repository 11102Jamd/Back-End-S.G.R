<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products'; 

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        
    ];
}