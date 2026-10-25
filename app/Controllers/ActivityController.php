<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Models\Activity;
use App\Views\ActivityView;

/**
 * Controlador del catalogo de actividades.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.0
 * @since     2026-10-25
 * @updated   2026-10-25
 * @package   App\Controllers
 *
 * @change    1.0.0 - 2026-10-25 - Fernando Luis Gil Correa - Creacion del listado y del detalle de las actividades.
 */
class ActivityController extends Controller
{
    /**
     * Lista las actividades del catalogo.
     *
     * El catalogo se devuelve completo, sin paginacion, porque alimenta el
     * selector de actividad del formulario de licitaciones. Con el parametro
     * `search` se puede filtrar por cualquiera de los nombres o codigos.
     */
    public function index(Request $request): Response
    {
        $query = Activity::query();
        $search = $request->query('search');

        if (is_string($search) && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $like = '%' . $search . '%';

                $builder->where('product_name', 'like', $like)
                    ->orWhere('product_code', 'like', $like)
                    ->orWhere('segment_name', 'like', $like)
                    ->orWhere('family_name', 'like', $like)
                    ->orWhere('class_name', 'like', $like);
            });
        }

        $activities = $query->orderBy('product_name')->get();

        return Response::json([
            'data' => ActivityView::collection($activities)
        ]);
    }

    # Muestra el detalle de una actividad.
    public function show(Request $request): Response
    {
        $activity = Activity::find((int) $request->param('id'));

        if ($activity === null) {
            return Response::json([
                'error' => 'Activity not found.'
            ], 404);
        }

        return Response::json([
            'data' => ActivityView::make($activity)
        ]);
    }
}
