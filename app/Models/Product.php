<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
Use App\Models\SaleProduct;
Use App\Models\ProductProduction;


class Product extends Model
{
    protected $table = 'product';
    
    protected $fillable = 
    [
        
        'product_name',
        'unit_price',
    ];

    public function saleProducts(): HasMany 
    {   
        return $this->hasMany(SaleProduct::class, 'product_id');
    }


    public function productProductions(): HasMany 
    {   
        return $this->hasMany(ProductProduction::class, 'product_id');
    }
    
}
