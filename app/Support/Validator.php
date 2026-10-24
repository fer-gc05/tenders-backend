<?php

namespace App\Support;

/**
 * Validador de datos con reglas declarativas.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-24
 * @package App\Support
 */
class Validator
{
    private array $errors = []; # Errores de validación.

    # Constructor del validador.
    public function __construct(
    private array $data, # Datos a validar.
    private array $rules, # Reglas de validación.
    private array $messages = [], # Mensajes de error personalizados.
    private array $attributes = [], # Nombres personalizados de los atributos.
    )
    {}

    # Ejecuta la validación de todos los campos.
    public function passes(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            foreach (explode('|', $ruleString) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->check($field, $name, $param);
            }
        }

        return $this->errors === [];
    }

    # Retorna los errores de validación.
    public function errors(): array
    {
        return $this->errors;
    }

    # Aplica una regla sobre un campo.
    private function check(string $field, string $rule, ?string $param): bool
    {
        $value = $this->data[$field] ?? null;
        $empty = $value === null || $value === '' || $value === [];

        if ($rule === 'nullable') {
            return true;
        }

        if ($empty && $rule !== 'required') {
            return true;
        }

        $valid = match ($rule) {
            'required' => !$empty,
            'string' => is_string($value),
            'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'numeric' => is_numeric($value),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null,
            'array' => is_array($value),
            'date' => is_string($value) && strtotime($value) !== false,
            'in' => in_array((string) $value, explode(',', (string) $param), true),
            'min' => $this->checkMin($value, (int) $param),
            'max' => $this->checkMax($value, (int) $param),
            default => true,
        };

        if (!$valid) {
            $this->addError($field, $rule, $param);
        }

        return $valid;
    }

    # Verifica el mínimo de un valor (número, longitud o cantidad).
    private function checkMin(mixed $value, int $min): bool
    {
        if (is_array($value)) {
            return count($value) >= $min;
        }

        if (is_int($value) || is_float($value)) {
            return $value >= $min;
        }

        return mb_strlen((string) $value) >= $min;
    }

    # Verifica el máximo de un valor (número, longitud o cantidad).
    private function checkMax(mixed $value, int $max): bool
    {
        if (is_array($value)) {
            return count($value) <= $max;
        }

        if (is_int($value) || is_float($value)) {
            return $value <= $max;
        }

        return mb_strlen((string) $value) <= $max;
    }

    # Registra el error de validación de un campo.
    private function addError(string $field, string $rule, ?string $param): void
    {
        $message = $this->messages[$field . '.' . $rule]
            ?? $this->defaultMessage($field, $rule, $param);

        $this->errors[$field][] = $message;
    }

    # Genera el mensaje de error por defecto.
    private function defaultMessage(string $field, string $rule, ?string $param): string
    {
        $attribute = $this->attributes[$field] ?? $field;

        return match ($rule) {
            'required' => "The {$attribute} field is required.",
            'string' => "The {$attribute} field must be a string.",
            'email' => "The {$attribute} field must be a valid email address.",
            'integer' => "The {$attribute} field must be an integer.",
            'numeric' => "The {$attribute} field must be a number.",
            'boolean' => "The {$attribute} field must be true or false.",
            'array' => "The {$attribute} field must be an array.",
            'date' => "The {$attribute} field must be a valid date.",
            'in' => "The selected {$attribute} is invalid.",
            'min' => "The {$attribute} field must be at least {$param}.",
            'max' => "The {$attribute} field may not be greater than {$param}.",
            default => "The {$attribute} field is invalid.",
        };
    }
}
