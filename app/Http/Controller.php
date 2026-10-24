<?php

namespace App\Http;

/**
 * Controlador base de la aplicación.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-22
 * @package App\Http
 */
abstract class Controller
{
    # Crea una respuesta JSON para el cliente.
    protected function response(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}
