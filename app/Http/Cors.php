<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Headers CORS de la API.
 *
 * La API se consume desde un frontend que puede estar en otro origen
 * (por ejemplo, el servidor de desarrollo de Vite en el puerto 5173 o el
 * dominio de despliegue), de modo que las peticiones del navegador exigen
 * una respuesta con los headers CORS y un preflight (OPTIONS) respondido con
 * 204 antes de la petición real.
 *
 * Los headers los emite la aplicación, no el servidor web: si el proxy
 * también los añadiera, la respuesta traería dos valores de
 * Access-Control-Allow-Origin y el navegador la rechazaría.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.1.0
 * @since     2026-10-26
 * @updated   2026-10-26
 * @package   App\Http
 *
 * @change    1.1.0 - 2026-10-26 - Fernando Luis Gil Correa - El origen permitido puede ser una lista separada por comas, y la respuesta devuelve el origen de la petición cuando está en la lista.
 * @change    1.0.0 - 2026-10-26 - Fernando Luis Gil Correa - Creacion de la clase.
 */
class Cors
{
    # Métodos HTTP permitidos en las peticiones cross-origin.
    public const ALLOWED_METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';

    # Headers de la petición permitidos en las peticiones cross-origin.
    public const ALLOWED_HEADERS = 'Content-Type, Authorization, Accept';

    # Tiempo (en segundos) que el navegador cachea el resultado del preflight.
    public const MAX_AGE = '86400';

    /**
     * Retorna los headers CORS que acompañan a todas las respuestas.
     *
     * Los orígenes permitidos se toman de la variable de entorno
     * CORS_ALLOWED_ORIGIN, que admite una lista separada por comas. El valor
     * `*` permite cualquier origen y es el comportamiento por defecto del
     * desarrollo local; en producción se debe fijar a los dominios del
     * frontend.
     *
     * Cuando la lista es concreta, la respuesta devuelve el origen de la
     * petición si está en la lista, y omite el header si no lo está: así el
     * navegador bloquea los orígenes no permitidos.
     */
    public static function headers(): array
    {
        $allowed = self::allowedOrigins();

        $headers = [
            'Access-Control-Allow-Methods' => self::ALLOWED_METHODS,
            'Access-Control-Allow-Headers' => self::ALLOWED_HEADERS,
            'Access-Control-Max-Age' => self::MAX_AGE,
        ];

        if (in_array('*', $allowed, true)) {
            $headers['Access-Control-Allow-Origin'] = '*';

            return $headers;
        }

        # La respuesta cambia según el origen de la petición, de modo que los
        # cachés no reutilicen la respuesta de un origen para otro.
        $headers['Vary'] = 'Origin';

        $origin = self::requestOrigin();

        if ($origin !== null && in_array($origin, $allowed, true)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        }

        return $headers;
    }

    /**
     * Emite los headers CORS.
     *
     * Se usa en las respuestas que no pasan por `Response` (la descarga del
     * archivo de un documento y el reporte Excel), que escriben sus propios
     * headers y terminan la petición.
     */
    public static function apply(): void
    {
        foreach (self::headers() as $name => $value) {
            header($name . ': ' . $value);
        }
    }

    # Retorna la lista de orígenes permitidos.
    private static function allowedOrigins(): array
    {
        $configured = self::configuredOrigin();

        $origins = array_values(array_filter(array_map(
            static fn (string $origin): string => trim($origin),
            explode(',', $configured)
        )));

        return $origins === [] ? ['*'] : $origins;
    }

    /*
     * Retorna el valor configurado de CORS_ALLOWED_ORIGIN.
     *
     * Se lee de $_ENV (donde Dotenv deja las variables del archivo .env) y,
     * como respaldo, de getenv() (donde el sistema deja las variables
     * exportadas en la shell), porque la configuración de PHP no siempre
     * incluye E en variables_order.
     */
    private static function configuredOrigin(): string
    {
        $origin = $_ENV['CORS_ALLOWED_ORIGIN'] ?? '';

        if (!is_string($origin) || trim($origin) === '') {
            $origin = getenv('CORS_ALLOWED_ORIGIN');
        }

        return is_string($origin) ? $origin : '';
    }

    # Retorna el origen de la petición, si el navegador lo envía.
    private static function requestOrigin(): ?string
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        return is_string($origin) && $origin !== '' ? $origin : null;
    }
}
