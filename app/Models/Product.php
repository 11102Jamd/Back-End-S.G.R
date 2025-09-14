<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SaleProduct;
use App\Models\ProductProduction;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'product';

    protected $fillable =
    [
        'product_name',
        'unit_price',
    ];

    protected $dates = ['deleted_at'];

    public function saleProducts(): HasMany
    {
        return $this->hasMany(SaleProduct::class, 'product_id');
    }


    public function productProductions(): HasMany
    {
        return $this->hasMany(ProductProduction::class, 'product_id');
    }
}
