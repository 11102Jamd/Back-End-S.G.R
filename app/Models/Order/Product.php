<?php

namespace App\Models\Order;


use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Fabricacion\Manufacturing;
use App\Models\Order\OrderDetail;



class Product extends Model
{
    protected $table = 'products'; 
    protected $primaryKey = 'id'; 

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'product_name',      
        'initial_quantity',  
        'current_stock',     
        'unit_price',        
    ];

    // Conversiones de tipos de atributos
    protected $casts = [
        'unit_price' => 'decimal:2', // Asegura 2 decimales para el precio
    ];

    // Relación con Manufacturing (Fabricación)
    public function manufacturing(): HasMany
    {
        return $this->hasMany(Manufacturing::class);
    }

    // Relación con OrderDetail (Detalles de pedido)
    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    // Eventos del modelo (validaciones adicionales)
    protected static function boot()
    {
        parent::boot();

        // Validación al guardar (create/update)
        static::saving(function ($model) {
            // Validación de stock vs cantidad inicial
            if ($model->current_stock > $model->initial_quantity) {
                throw ValidationException::withMessages([
                    'current_stock' => 'El stock actual no puede superar la cantidad inicial'
                ]);
            }

            // Validación de precio mínimo si tiene pedidos
            if ($model->orderDetails()->exists() && $model->unit_price < 50) {
                throw new \Exception("Productos con pedidos no pueden tener precio menor a 50");
            }
        });
    }

    // Accesor para precio formateado
    public function getFormattedPriceAttribute()
    {
        return '$ ' . number_format($this->unit_price, 2);
    }

    // Accesor para stock disponible calculado
    public function getAvailableStockAttribute()
    {
        return $this->current_stock - $this->orderDetails->sum('quantity');
    }
}