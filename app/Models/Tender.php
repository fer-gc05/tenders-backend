<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * Modelo de la tabla tenders(licitaciones-ofertas).
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-24
 * @updated 2026-10-24
 * @package App\Models
 * 
 * @change 1.0.0 - Fernando Luis Gil Correa - Creación del modelo.
 */
class Tender extends Model
{
    protected $table = 'tenders';
    protected $fillable = [
        'consecutive_code',
        'subject',
        'description',
        'currency',
        'budget',
        'activity_id',
        'start_date',
        'start_time',
        'close_date',
        'close_time',
        'status',
    ];

    protected $casts = [
        'activity_id' => 'integer',
    ];

    # Relación con la tabla activities(actividades).
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    # Relación con la tabla tender_documents(documentos de licitaciones-ofertas).
    public function documents(): HasMany
    {
        return $this->hasMany(TenderDocument::class);
    }
}