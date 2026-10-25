<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * Modelo de la tabla activities(actividades).
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.1
 * @since     2026-10-24
 * @updated   2026-10-25
 * @package   App\Models
 *
 * @change    1.0.1 - 2026-10-25 - Fernando Luis Gil Correa - Normalización del encabezado de documentación.
 */
class Activity extends Model
{
    protected $table = 'activities';
    protected $fillable = [
        'segment_code',
        'segment_name',
        'family_code',
        'family_name',
        'class_code',
        'class_name',
        'product_code',
        'product_name'
    ];

    protected $casts = [
        'segment_code' => 'integer',
        'family_code' => 'integer',
        'class_code' => 'integer',
    ];

    # Relación con la tabla tenders(licitaciones-ofertas).
    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }
}