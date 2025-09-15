<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * Modelo ProductionConsumption
 *
 * Representa el consumo de un insumo dentro de una producción específica.
 * Contiene la relación entre producción, insumo, lote, y los datos de cantidad y costos.
 */
class ProductionConsumption extends Model
{
    use HasFactory;
    protected $table = 'production_consumptions';

    protected $fillable = [
        'production_id',
        'input_id',
        'input_batches_id',
        'quantity_used',
        'unit_price',
        'total_cost',
    ];

    /**
     * Relación: Obtiene la producción a la que pertenece este consumo.
     *
     * @return BelongsTo
     */
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class, 'production_id');
    }

    /**
     * Relación: Obtiene el insumo que fue consumido.
     *
     * @return BelongsTo
     */
    public function input(): BelongsTo
    {
        return $this->belongsTo(Input::class, 'input_id');
    }

    /**
     * Relación: Obtiene el lote del insumo que fue utilizado.
     *
     * @return BelongsTo
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(InputBatch::class, 'input_batches_id');
    }
}
