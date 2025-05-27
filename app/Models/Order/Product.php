<?php

namespace App\Models\Order;


//use App\Models\Fabricacion\Manufacturing;

use App\Models\Manufacturing\Manufacturing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'product';

    protected $fillable = [
        'ProductName',
        'InitialQuantity',
        'CurrentStock',
        'UnityPrice'
    ];

    public function manufacturing(): HasMany
    {
        return $this->hasMany(Manufacturing::class, 'ID_product');
    }

    // public function orderDetails(): HasMany
    // {
    //     return $this->hasMany()
    // }
}
