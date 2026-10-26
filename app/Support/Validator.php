<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Validador de datos con reglas declarativas.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.2.0
 * @since     2026-10-24
 * @updated   2026-10-25
 * @package   App\Support
 *
 * @change    1.2.0 - 2026-10-25 - Fernando Luis Gil Correa - min/max
 *            interpretan el valor según el tipo declarado en las reglas del
 *            campo, de modo que un código numérico como "10101501" se valida
 *            como texto de 8 caracteres y no como el entero 10.101.501.
 * @change    1.1.0 - 2026-10-24 - Fernando Luis Gil Correa - Semántica
 *            numérica en min/max, fecha ISO estricta, nueva regla time y
 *            rechazo de booleanos en integer.
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
    #
    # Un campo vacío (null, '' o []) solo se evalúa con la regla `required`;
    # el resto de reglas lo omiten. Así, "nullable" es implícito: cualquier
    # regla distinta de `required` admite que el campo falte o esté vacío.
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
            # filter_var convierte `true` en el entero 1; los booleanos no son
            # enteros válidos en el contrato de la API y se rechazan.
            'integer' => !is_bool($value) && filter_var($value, FILTER_VALIDATE_INT) !== false,
            'numeric' => is_numeric($value),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null,
            'array' => is_array($value),
            'date' => $this->isIsoDate($value),
            'time' => $this->isTime($value),
            'in' => in_array((string) $value, explode(',', (string) $param), true),
            'min' => $this->checkMin($field, $value, (float) $param),
            'max' => $this->checkMax($field, $value, (float) $param),
            default => true,
        };

        if (!$valid) {
            $this->addError($field, $rule, $param);
        }

        return $valid;
    }

    # Verifica el mínimo de un valor.
    #
    # La regla `min` (y `max`) interpreta el valor según el tipo declarado en
    # las reglas del campo: un campo `numeric` o `integer` se compara por su
    # valor, un campo `array` por la cantidad de elementos y cualquier otro
    # (por ejemplo `string`) por la longitud del texto. Así, un código
    # numérico como "10101501" se valida como texto de 8 caracteres y no como
    # el entero 10.101.501, mientras que un presupuesto enviado como
    # "15000000.00" sigue acotado por su valor.
    private function checkMin(string $field, mixed $value, float $min): bool
    {
        $mode = $this->sizeMode($field);

        if ($mode === 'array') {
            return is_array($value) ? count($value) >= $min : true;
        }

        if ($mode === 'numeric' && is_numeric($value)) {
            return (float) $value >= $min;
        }

        return mb_strlen((string) $value) >= $min;
    }

    # Verifica el máximo de un valor (número, longitud o cantidad).
    private function checkMax(string $field, mixed $value, float $max): bool
    {
        $mode = $this->sizeMode($field);

        if ($mode === 'array') {
            return is_array($value) ? count($value) <= $max : true;
        }

        if ($mode === 'numeric' && is_numeric($value)) {
            return (float) $value <= $max;
        }

        return mb_strlen((string) $value) <= $max;
    }

    # Determina cómo se compara el tamaño de un campo.
    #
    # El tipo lo declaran las reglas del propio campo: `numeric` e `integer`
    # comparan por valor, `array` por cantidad de elementos y el resto por la
    # longitud del texto. El valor recibido no decide, porque un código como
    # "10101501" es un texto aunque solo tenga dígitos.
    private function sizeMode(string $field): string
    {
        $names = array_map(
            static fn (string $rule): string => explode(':', $rule, 2)[0],
            explode('|', $this->rules[$field] ?? '')
        );

        if (in_array('integer', $names, true) || in_array('numeric', $names, true)) {
            return 'numeric';
        }

        if (in_array('array', $names, true)) {
            return 'array';
        }

        return 'string';
    }

    # Comprueba una fecha en formato ISO `YYYY-MM-DD`.
    #
    # El validador no usa strtotime, porque es demasiado permisivo (acepta
    # expresiones como "next monday" o "2026/01/01"). La API solo acepta el
    # formato ISO del contrato y checkdate() descarta fechas imposibles como
    # el 31 de febrero.
    private function isIsoDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) !== 1) {
            return false;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    # Comprueba una hora en formato `HH:MM` o `HH:MM:SS` (00:00:00 a 23:59:59).
    #
    # La columna `time` de MySQL/MariaDB admite valores mayores (p. ej.
    # "838:59:59"), pero el contrato de la API solo acepta horas de un día
    # civil, que es lo que tienen sentido en una programación de licitación.
    private function isTime(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value) === 1;
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
            'time' => "The {$attribute} field must be a valid time.",
            'in' => "The {$attribute} field is invalid.",
            'min' => "The {$attribute} field must be at least {$param}.",
            'max' => "The {$attribute} field may not be greater than {$param}.",
            default => "The {$attribute} field is invalid.",
        };
    }
}
