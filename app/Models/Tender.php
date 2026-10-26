<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * Modelo de la tabla tenders(licitaciones-ofertas).
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.3
 * @since     2026-10-24
 * @updated   2026-10-26
 * @package   App\Models
 *
 * @change    1.0.3 - 2026-10-26 - Fernando Luis Gil Correa - El consecutivo usa el prefijo O- en lugar de PO-.
 * @change    1.0.2 - 2026-10-25 - Fernando Luis Gil Correa - Corrección del cast de las horas y del generador de consecutivos.
 * @change    1.0.1 - 2026-10-24 - Fernando Luis Gil Correa - Creacion metodo generador de consecutivos.
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

    /**
     * Los casts son los que hacen que las fechas y las horas lleguen como
     * objetos y no como cadenas. El cast "datetime" también funciona sobre las
     * columnas TIME de la base de datos y devuelve la hora sin fecha.
     */
    protected $casts = [
        'activity_id' => 'integer',
        'start_date' => 'date:Y-m-d',
        'start_time' => 'datetime:H:i:s',
        'close_date' => 'date:Y-m-d',
        'close_time' => 'datetime:H:i:s',
        'budget' => 'decimal:2',
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

    /**
     * Generar consecutivo siguiente.
     *
     * El número se deriva de los consecutivos ya guardados del año en curso,
     * por eso el contador reinicia solo cada 1 de enero. El formato es
     * O-{3 ceros a la izquierda}{consecutivo}-{año en 2 dígitos}.
     *
     * Los consecutivos históricos con el prefijo PO- no se migran: el
     * contador solo mira los registros O- del año en curso, de modo que el
     * primer código O- de cada año arranca en 0001.
     */
    public static function nextConsecutiveCode(): string
    {
        $year = substr((string) date('Y'), -2);

        $last = (int) static::query()
            ->where('consecutive_code', 'like', "O-%-{$year}")
            ->selectRaw('MAX(CAST(SUBSTRING(consecutive_code, 3, 4) AS UNSIGNED)) AS last')
            ->value('last');

        return sprintf('O-%04d-%s', $last + 1, $year);
    }
}