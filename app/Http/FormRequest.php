<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\ValidationException;
use App\Support\Validator;

/**
 * Solicitud HTTP con validación declarativa.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.1.0
 * @since     2026-10-24
 * @updated   2026-10-24
 * @package   App\Http
 *
 * @change    1.1.0 - 2026-10-24 - Fernando Luis Gil Correa - Hook after()
 *            para validaciones de negocio posteriores a las reglas
 *            declarativas.
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
    #
    # Primero se evalúan las reglas declarativas (presencia, tipo, formato,
    # rango). Si todas pasan, se invoca el hook after(), donde cada solicitud
    # concreta puede agregar validaciones de negocio que las reglas no
    # expresan, por ejemplo que una clave foránea exista o que un código sea
    # único en la base de datos. Así, el Validator sigue siendo una clase pura
    # que nunca consulta la base de datos.
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

        $errors = $this->after();

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    # Validaciones de negocio posteriores a las reglas declarativas.
    #
    # Retorna un mapa campo => mensajes de error (en inglés, como el resto de
    # la API). La implementación base no agrega ningún error.
    protected function after(): array
    {
        return [];
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
