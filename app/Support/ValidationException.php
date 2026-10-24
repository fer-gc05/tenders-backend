<?php

namespace App\Support;

use Exception;

/**
 * Excepción de validación de datos.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-24
 * @package App\Support
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
