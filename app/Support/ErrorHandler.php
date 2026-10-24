<?php

namespace App\Support;

use App\Http\Response;
use ErrorException;
use Throwable;

/**
 * Manejador global de errores y excepciones.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-22
 * @package App\Support
 */
class ErrorHandler
{
    # Registra los manejadores de errores, excepciones y cierre.
    public static function register(): void
    {
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new ErrorException($message, 0, $severity, $file, $line);
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

    # Envia la excepcion como respuesta JSON.
    public static function handle(Throwable $e): void
    {
        if ($e instanceof ValidationException) {
            (new Response([
                'error' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422))->send();
            return;
        }

        $local = ($_ENV['APP_ENV'] ?? 'production') === 'local';

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
