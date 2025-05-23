<?php

namespace App\Models\Order;

use App\Models\Fabricacion\Manufacturing;
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

    public function manufacturings(): HasMany
    {
        return $this->hasMany(Manufacturing::class);
    }

    // public function orderDetails(): HasMany
    // {
    //     return $this->hasMany()
    // }
}
