<?php

/**
 * Definición de rutas de la API.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-24
 * @package Routes
 */

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

# Aqui se registran los controladores:
# $router->get('/api/v1/licitaciones', [LicitacionController::class, 'index']);

return $router;
