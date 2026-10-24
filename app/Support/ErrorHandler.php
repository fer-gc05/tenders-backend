<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Response;
use ErrorException;
use Throwable;

/**
 * Manejador global de errores y excepciones.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.1.0
 * @since     2026-10-22
 * @updated   2026-10-24
 * @package   App\Support
 *
 * @change    1.1.0 - 2026-10-24 - Fernando Luis Gil Correa - Los avisos y
 *            deprecaciones se registran en el log en lugar de tumbar la
 *            petición, y los errores de producción quedan también en el log.
 */
class ErrorHandler
{
    # Registra los manejadores de errores, excepciones y cierre.
    public static function register(): void
    {
        # Errores recuperables (avisos, advertencias y errores de usuario).
        #
        # Solo se convierten en excepción los que delatan un fallo real de la
        # aplicación (E_WARNING, E_USER_WARNING, E_USER_ERROR,
        # E_RECOVERABLE_ERROR). Los avisos y las deprecaciones —habituales en
        # las dependencias— se escriben en el log y no interrumpen la
        # petición; de lo contrario, cualquier deprecación de un paquete de
        # vendor/ tumbaría la respuesta con un 500. Al devolver true, PHP no
        # ejecuta además su manejador interno.
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (in_array($severity, [E_WARNING, E_USER_WARNING, E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }

            error_log("PHP [{$severity}] {$message} en {$file}:{$line}");
            return true;
        });

        set_exception_handler(function (Throwable $e): void {
            self::handle($e);
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();

            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::handle(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
            }
        });
    }

    # Envía la excepción como respuesta JSON.
    public static function handle(Throwable $e): void
    {
        if ($e instanceof ValidationException) {
            (new Response([
                'error' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422))->send();
            return;
        }

        # Cualquier otra excepción se registra en el log con su traza. Bajo
        # php-fpm, error_log escribe en el registro del servicio
        # (journalctl -u php-fpm), sin archivos ni configuración adicionales.
        error_log($e);

        $local = ($_ENV['APP_ENV'] ?? 'production') === 'local';

        # En local se devuelve el detalle (mensaje, archivo y traza) para
        # depurar; en producción, un mensaje genérico. Este detalle lo decide
        # el entorno (APP_ENV), no una variable de depuración aparte.
        (new Response(
            $local
                ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]
                : ['error' => 'Internal server error.'],
            500
        ))->send();
    }
}
