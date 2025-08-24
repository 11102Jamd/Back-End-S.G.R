<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
Use App\Models\User;
Use App\Models\SaleProduct;

class Sale extends Model
{
    protected $table = 'sale';
    
    protected $fillable = 
    [
        'user_id',
        'sale_date',
        'sale_total',
    ];

    Public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    Public function saleProducts(): HasMany
    {
        return $this->hasMany(SaleProduct::class, 'sale_id');
    }

}
