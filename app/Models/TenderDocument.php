<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de la tabla tender_documents(documentos de licitaciones-ofertas).
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-24
 * @updated 2026-10-24
 * @package App\Models
 * 
 * @change 1.0.0 - Fernando Luis Gil Correa - Creación del modelo.
 */
class TenderDocument extends Model
{
    protected $table = 'tender_documents';
    protected $fillable = [
        'tender_id',
        'title',
        'file_path'
    ];

    protected $casts = [
        'tender_id' => 'integer',
    ];

    # Relación con la tabla tenders(licitaciones-ofertas).
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }
}