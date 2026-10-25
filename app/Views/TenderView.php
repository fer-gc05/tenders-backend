<?php

declare(strict_types=1);

namespace App\Views;

use App\Models\Activity;
use App\Models\Tender;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Serializacion de licitaciones.
 * 
 * @author    Fernando Luis Gil Correa
 * @version   1.0.1
 * @since     2026-10-24
 * @updated   2026-10-25
 * @package   App\Views
 *
 * @change    1.0.1 - 2026-10-25 - Fernando Luis Gil Correa - Corrección de la clave de la descripción y del tipo de la actividad.
 */
class TenderView
{
    # Serializa una licitacion.
    public static function make(Tender $tender): array
    {
        return [
            'id' => $tender->id,
            'consecutive_code' => $tender->consecutive_code,
            'subject' => $tender->subject,
            'description' => $tender->description,
            'currency' => $tender->currency,
            'budget' => $tender->budget,
            'activity_id' => $tender->activity_id,
            'start_date' => $tender->start_date?->format('Y-m-d'),
            'start_time' => $tender->start_time?->format('H:i:s'),
            'close_date' => $tender->close_date?->format('Y-m-d'),
            'close_time' => $tender->close_time?->format('H:i:s'),
            'status' => $tender->status,
            'created_at' => $tender->created_at?->toIso8601String(),
            'updated_at' => $tender->updated_at?->toIso8601String()
        ];
    }

    public static function collection(iterable $tenders): array
    {
        $data = [];

        foreach ($tenders as $tender) {
            $data[] = self::make($tender);
        }
        return $data;
    }

    public static function paginated(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => self::collection($paginator->items()),
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage()
            ]
        ];
    }

    public static function detail(Tender $tender, ?Activity $activity, iterable $documents = []): array
    {
        return self::make($tender) + [
            'activity' => $activity === null ? null : [
                'id' => $activity->id,
                'segment_code' => $activity->segment_code,
                'segment_name' => $activity->segment_name,
                'family_code' => $activity->family_code,
                'family_name' => $activity->family_name,
                'class_code' => $activity->class_code,
                'class_name' => $activity->class_name,
                'product_code' => $activity->product_code,
                'product_name' => $activity->product_name
            ],
            'documents' => DocumentView::collection($documents)
        ];
    }

}