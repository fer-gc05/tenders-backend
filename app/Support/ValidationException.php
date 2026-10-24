<?php

declare(strict_types=1);

namespace App\Support;

use Exception;

/**
 * Excepción de validación de datos.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.1
 * @since     2026-10-24
 * @updated   2026-10-24
 * @package   App\Support
 *
 * @change    1.0.1 - 2026-10-24 - Fernando Luis Gil Correa - Tipos
 *            estrictos.
 */
class ValidationException extends Exception
{
    # Constructor de la excepción de validación.
    public function __construct(private array $errors, string $message = 'Validation error.')
    {
        parent::__construct($message);
    }

    # Retorna los errores de validación.
    public function errors(): array
    {
        return $this->errors;
    }
}
