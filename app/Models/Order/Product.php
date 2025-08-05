<?php

namespace App\Models\Order;


use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Fabricacion\Manufacturing;
use App\Models\Order\OrderDetail;



class Product extends Model
{
    protected $table = 'product'; 
    protected $primaryKey = 'id'; 

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'ProductName',      
        'InitialQuantity',  
        'CurrentStock',     
        'UnityPrice',        
    ];

    // Conversiones de tipos de atributos
    protected $casts = [
        'UnityPrice' => 'decimal:2', // Asegura 2 decimales para el precio
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
            if ($model->CurrentStock > $model->InitialQuantity) {
                throw ValidationException::withMessages([
                    'CurrentStock' => 'El stock actual no puede superar la cantidad inicial'
                ]);
            }

            // Validación de precio mínimo si tiene pedidos
            if ($model->orderDetails()->exists() && $model->UnitPrice < 50) {
                throw new \Exception("Productos con pedidos no pueden tener precio menor a 50");
            }
        });
    }

    // Accesor para precio formateado
    public function getFormattedPriceAttribute()
    {
        return '$ ' . number_format($this->unit_price, 3, ',', '.');
    }

    // Accesor para stock disponible calculado
    public function getAvailableStockAttribute()
    {
        return $this->CurrentStock - $this->orderDetails->sum('quantity');
    }
}