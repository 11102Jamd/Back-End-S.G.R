<?php

namespace App\Models\Fabricacion;

use Illuminate\Database\Eloquent\Model;

class Recipes extends Model
{
    protected $table = 'recipe';
    
    protected $fillable = [
        'ID_inputs',
        'AmountSpent',
        'UnitMeasurement',
        'PriceQuantitySpent',
    ];

    public function Manufacturing()
    {
        return $this->belongsTo(Manufacturing::class, 'ID_manufacturing');
    }
}
