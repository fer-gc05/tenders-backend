<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Controller;
use App\Http\Request;
use App\Http\Requests\Tenders\StoreTenderRequest;
use App\Http\Requests\Tenders\UpdateTenderRequest;
use App\Http\Response;
use App\Models\Tender;
use App\Models\TenderDocument;
use App\Views\TendersXlsxView;
use App\Views\TenderView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Throwable;

/**
 * Controlador de las licitaciones.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.5
 * @since     2026-10-24
 * @updated   2026-10-26
 * @package   App\Controllers
 *
 * @change    1.0.5 - 2026-10-26 - Fernando Luis Gil Correa - Los documentos se guardan con el título y la descripción que envía el cliente.
 * @change    1.0.4 - 2026-10-25 - Fernando Luis Gil Correa - Descarga del reporte Excel del listado de licitaciones.
 * @change    1.0.3 - 2026-10-25 - Fernando Luis Gil Correa - El buscador del listado filtra por consecutivo, objeto y descripción.
 * @change    1.0.2 - 2026-10-25 - Fernando Luis Gil Correa - Corrección del 404, del header Location, del guardado de documentos y de los filtros del listado.
 * @change    1.0.1 - 2026-10-25 - Fernando Luis Gil Correa - Corrección de sintaxis y re-throw en caso de excepción al crear.
 */
class TenderController extends Controller
{
    # Crea una licitacion con su consecutivo y sus documentos
    public function store(StoreTenderRequest $request): Response
    {
        $data = $request->validated();
        $files = [];

        try {
            $tender = $this->create($request, $data, $files);
        } catch (Throwable $th) {
            $this->cleanUpFiles($files);
            throw $th;
        }

        return Response::json(
            ['data' => TenderView::make($tender)],
            201,
            ['Location' => '/api/v1/tenders/' . $tender->id]
        );
    }

    public function update(UpdateTenderRequest $request): Response
    {
        $tender = Tender::find((int) $request->param('id'));

        if ($tender === null) {
            return Response::json([
                'error' => 'Tender not found.'
            ], 404);
        }

        $data = $request->validated();
        $files = [];

        try {
            $tender = (new Tender())->getConnection()->transaction(
                function () use ($request, $data, $tender, &$files): Tender {
                    $tender->update([
                        'subject' => $data['subject'],
                        'description' => $data['description'],
                        'currency' => $data['currency'],
                        'budget' => $data['budget'],
                        'activity_id' => (int) $data['activity_id'],
                        'start_date' => $data['start_date'],
                        'start_time' => $this->normalizeTime($data['start_time']),
                        'close_date' => $data['close_date'],
                        'close_time' => $this->normalizeTime($data['close_time']),
                        'status' => $data['status'] ?? $tender->status,

                    ]);

                    foreach ($request->uploads() as $file) {
                        $document = TenderDocument::storeUpload(
                            $file,
                            $tender,
                            $request->documentMetadata((int) $file['index'])
                        );
                        $files[] = $document->file_path;
                    }

                    return $tender->fresh(['activity', 'documents']);
                }
            );
        } catch (Throwable $th) {
            $this->cleanUpFiles($files);
            throw $th;
        }

        return Response::json([
            'data' => TenderView::detail($tender, $tender->activity, $tender->documents)
        ]);
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) ($request->query('page') ?: 1));
        $perPage = min(50, max(1, (int) ($request->query('per_page') ?? 15)));
        $paginator = $this->filtered($request)->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return Response::json(TenderView::paginated($paginator));
    }

    /**
     * Descarga el reporte Excel del listado de licitaciones.
     *
     * Aplica los mismos filtros del listado, pero sin paginacion: el reporte
     * contiene todas las licitaciones que cumplen los filtros.
     */
    public function export(Request $request): Response
    {
        $tenders = $this->filtered($request)->orderByDesc('id')->get();
        $binary = TendersXlsxView::build($tenders);
        $name = TendersXlsxView::filename();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Length: ' . (string) strlen($binary));
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('X-Content-Type-Options: nosniff');

        echo $binary;
        exit;
    }

    /**
     * Aplica los filtros del listado a la consulta de licitaciones.
     *
     * El listado y el reporte Excel comparten estos filtros, de modo que el
     * reporte muestra exactamente la misma informacion que la vista.
     */
    private function filtered(Request $request): Builder
    {
        $query = Tender::query()->with('activity');
        $status = $request->query('status');

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $activity = $request->query('activity_id');
        if (is_string($activity) && $activity !== '') {
            $query->where('activity_id', (int) $activity);
        }

        $search = $request->query('search');
        if (is_string($search) && $search !== '') {
            $like = '%' . $search . '%';

            /*
             * El buscador del listado filtra por consecutivo, objeto o
             * descripcion, que son las columnas que muestra la vista.
             */
            $query->where(function ($builder) use ($like): void {
                $builder->where('consecutive_code', 'like', $like)
                    ->orWhere('subject', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        return $query;
    }

    public function show(Request $request): Response
    {
        $tender = Tender::with(['activity', 'documents'])->find((int) $request->param('id'));

        if ($tender === null) {
            return Response::json([
                'error' => 'Tender not found.'
            ], 404);
        }

        return Response::json([
            'data' => TenderView::detail($tender, $tender->activity, $tender->documents)
        ]);

    }



    private function create(StoreTenderRequest $request, array $data, array &$files): Tender
    {
        $intents = 3;

        while (true) {
            try {
                return (new Tender())->getConnection()->transaction(
                    function () use ($request, $data, &$files): Tender {
                        $tender = Tender::create([
                            'consecutive_code' => Tender::nextConsecutiveCode(),
                            'subject' => $data['subject'],
                            'description' => $data['description'],
                            'currency' => $data['currency'],
                            'budget' => $data['budget'],
                            'activity_id' => (int) $data['activity_id'],
                            'start_date' => $data['start_date'],
                            'start_time' => $this->normalizeTime($data['start_time']),
                            'close_date' => $data['close_date'],
                            'close_time' => $this->normalizeTime($data['close_time']),
                            'status' => $data['status'] ?? 'draft',
                        ]);

                        foreach ($request->uploads() as $file) {
                            $document = TenderDocument::storeUpload(
                                $file,
                                $tender,
                                $request->documentMetadata((int) $file['index'])
                            );
                            $files[] = $document->file_path;
                        }

                        return $tender;
                    }
                );
            } catch (QueryException $e) {
                $intents--;
                if (!$this->isDuplicateConsecutive($e) || $intents <= 0) {
                    throw $e;
                }
            }
        }
    }

    private function cleanUpFiles(array $files): void
    {
        foreach ($files as $file) {
            TenderDocument::deleteFile($file);
        }
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    private function isDuplicateConsecutive(QueryException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), 'consecutive_code');
    }
}