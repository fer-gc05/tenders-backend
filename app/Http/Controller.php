<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Controlador base de la aplicación.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.1
 * @since     2026-10-22
 * @updated   2026-10-24
 * @package   App\Http
 *
 * @change    1.0.1 - 2026-10-24 - Fernando Luis Gil Correa - Tipos
 *            estrictos.
 */
abstract class Controller
{
    # Crea una respuesta JSON para el cliente.
    protected function response(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}
