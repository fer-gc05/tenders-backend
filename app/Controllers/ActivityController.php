<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Controller;
use App\Http\Request;
use App\Http\Requests\Activities\StoreActivityRequest;
use App\Http\Requests\Activities\UpdateActivityRequest;
use App\Http\Response;
use App\Models\Activity;
use App\Views\ActivityView;
use Throwable;

/**
 * Controlador del catalogo de actividades.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.1
 * @since     2026-10-25
 * @updated   2026-10-25
 * @package   App\Controllers
 *
 * @change    1.0.1 - 2026-10-25 - Fernando Luis Gil Correa - Creacion del CRUD completo del catalogo de actividades.
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

    # Crea una actividad del catalogo.
    public function store(StoreActivityRequest $request): Response
    {
        try {
            $activity = Activity::create($request->validated());
        } catch (Throwable $th) {
            return Response::json([
                'error' => 'The activity could not be created.'
            ], 409);
        }

        return Response::json(
            ['data' => ActivityView::make($activity)],
            201,
            ['Location' => '/api/v1/activities/' . $activity->id]
        );
    }

    # Actualiza una actividad del catalogo.
    public function update(UpdateActivityRequest $request): Response
    {
        $activity = Activity::find((int) $request->param('id'));

        if ($activity === null) {
            return Response::json([
                'error' => 'Activity not found.'
            ], 404);
        }

        try {
            $activity->update($request->validated());
        } catch (Throwable $th) {
            return Response::json([
                'error' => 'The activity could not be updated.'
            ], 409);
        }

        return Response::json([
            'data' => ActivityView::make($activity->refresh())
        ]);
    }

    # Elimina una actividad del catalogo, salvo que tenga licitaciones asociadas.
    public function destroy(Request $request): Response
    {
        $activity = Activity::find((int) $request->param('id'));

        if ($activity === null) {
            return Response::json([
                'error' => 'Activity not found.'
            ], 404);
        }

        $tenders = $activity->tenders()->count();

        if ($tenders > 0) {
            return Response::json([
                'error' => 'The activity cannot be deleted because it has associated tenders.',
                'meta' => ['tenders' => $tenders]
            ], 409);
        }

        try {
            $activity->delete();
        } catch (Throwable $th) {
            return Response::json([
                'error' => 'The activity could not be deleted.'
            ], 409);
        }

        return new Response('', 204);
    }
}
