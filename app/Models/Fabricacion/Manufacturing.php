<?php

namespace App\Models\Fabricacion;

use Illuminate\Database\Eloquent\Model;

class Manufacturing extends Model
{
    protected $table = 'manufacturing';

    protected $fillable = [
        'ID_product',
        'ManufacturingTime',
        'Labour',
        'ManufactureProductG',
        'TotalCostProduction',
    ];

    public function Recipes()
    {
        return $this->hasMany(Recipes::class, 'ID_manufacturing');
    }
}