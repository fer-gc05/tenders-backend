<?php

declare(strict_types=1);

namespace App\Http\Requests\Activities;

use App\Http\FormRequest;

/**
 * Reglas comunes del catalogo de actividades.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.0
 * @since     2026-10-25
 * @updated   2026-10-25
 * @package   App\Http\Requests
 *
 * @change    1.0.0 - 2026-10-25 - Fernando Luis Gil Correa - Creacion de la clase con el espejo del schema del catalogo.
 */
class ActivityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'segment_code' => 'required|integer|min:1',
            'segment_name' => 'required|string|max:200',
            'family_code' => 'required|integer|min:1',
            'family_name' => 'required|string|max:200',
            'class_code' => 'required|integer|min:1',
            'class_name' => 'required|string|max:200',
            'product_code' => 'required|string|max:200',
            'product_name' => 'required|string|max:200'
        ];
    }

    protected function after(): array
    {
        $errors = [];

        /*
         * Los nombres obligatorios no pueden estar compuestos solo por
         * espacios: la regla required solo descarta la cadena vacia.
         */
        foreach (['segment_name', 'family_name', 'class_name', 'product_code', 'product_name'] as $field) {
            $value = $this->body($field);

            if (is_string($value) && trim($value) === '') {
                $errors[$field] = ["The {$field} field cannot contain only spaces."];
            }
        }

        return $errors;
    }
}
