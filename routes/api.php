<?php

declare(strict_types=1);

/**
 * Definición de rutas de la API.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.3
 * @since     2026-10-24
 * @updated   2026-10-25
 * @package   Routes
 *
 * @change    1.0.3 - 2026-10-25 - Fernando Luis Gil Correa - Registro de las rutas del catalogo de actividades.
 * @change    1.0.2 - 2026-10-25 - Fernando Luis Gil Correa - Registro de las rutas del CRUD de licitaciones y de la descarga de documentos.
 * @change    1.0.1 - 2026-10-24 - Fernando Luis Gil Correa - Tipos
 *            estrictos.
 */

use App\Controllers\ActivityController;
use App\Controllers\DocumentController;
use App\Controllers\TenderController;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

$router = new Router(Request::capture());

$router->get('/api/v1/status', function (Request $request): Response {
    return Response::json([
        'app' => $_ENV['APP_NAME'] ?? 'tenders_backend',
        'status' => 'ok',
    ]);
});

$router->get('/api/v1/activities', [ActivityController::class, 'index']);
$router->get('/api/v1/activities/{id}', [ActivityController::class, 'show']);
$router->post('/api/v1/tenders', [TenderController::class, 'store']);
$router->get('/api/v1/tenders', [TenderController::class, 'index']);
$router->get('/api/v1/tenders/{id}', [TenderController::class, 'show']);
$router->post('/api/v1/tenders/{id}', [TenderController::class, 'update']);
$router->put('/api/v1/tenders/{id}', [TenderController::class, 'update']);
$router->get('/api/v1/tenders/{id}/documents/{documentId}', [DocumentController::class, 'download']);

return $router;
