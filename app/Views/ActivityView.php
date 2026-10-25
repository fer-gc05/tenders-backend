<?php

declare(strict_types=1);

namespace App\Views;

use App\Models\Activity;

/**
 * Serializacion de las actividades(catalogo de productos y servicios).
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.0
 * @since     2026-10-25
 * @updated   2026-10-25
 * @package   App\Views
 *
 * @change    1.0.0 - 2026-10-25 - Fernando Luis Gil Correa - Creacion de la serializacion de las actividades.
 */
class ActivityView
{
    # Serializa una actividad.
    public static function make(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'segment_code' => $activity->segment_code,
            'segment_name' => $activity->segment_name,
            'family_code' => $activity->family_code,
            'family_name' => $activity->family_name,
            'class_code' => $activity->class_code,
            'class_name' => $activity->class_name,
            'product_code' => $activity->product_code,
            'product_name' => $activity->product_name,
            'created_at' => $activity->created_at?->toIso8601String(),
            'updated_at' => $activity->updated_at?->toIso8601String()
        ];
    }


    # Serializa una coleccion de actividades.
    public static function collection(iterable $activities): array
    {
        $data = [];

        foreach ($activities as $activity) {
            $data[] = self::make($activity);
        }

        return $data;
    }
}
