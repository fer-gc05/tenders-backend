<?php

namespace App\Http;

use App\Support\ValidationException;
use App\Support\Validator;

/**
 * Solicitud HTTP con validación declarativa.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-24
 * @package App\Http
 */
abstract class FormRequest extends Request
{
    # Reglas de validación (las define cada solicitud concreta).
    abstract public function rules(): array;

    # Mensajes de error personalizados.
    public function messages(): array
    {
        return [];
    }

    # Nombres personalizados de los atributos.
    public function attributes(): array
    {
        return [];
    }

    # Crea la solicitud de validación desde la solicitud capturada.
    public static function from(Request $request): static
    {
        return new static(
            $request->method(),
            $request->path(),
            $request->query(),
            $request->body(),
            $request->headers(),
            $request->params()
        );
    }

    # Ejecuta la validación (lanza ValidationException si falla).
    public function validate(): void
    {
        $validator = new Validator(
            $this->body(),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        if (!$validator->passes()) {
            throw new ValidationException($validator->errors());
        }
    }

    # Retorna únicamente los campos definidos en rules().
    public function validated(): array
    {
        return array_intersect_key(
            $this->body(),
            array_flip(array_keys($this->rules()))
        );
    }
}
