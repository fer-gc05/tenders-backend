<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenders;

use App\Http\FormRequest;
use App\Models\Activity;
use App\Models\TenderDocument;

/**
 * Reglas comunes de las licitaciones.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.3
 * @since     2026-10-24
 * @updated   2026-10-26
 * @package   App\Http\Requests
 *
 * @change    1.0.3 - 2026-10-26 - Fernando Luis Gil Correa - El presupuesto admite como máximo 2 decimales.
 * @change    1.0.2 - 2026-10-25 - Fernando Luis Gil Correa - El presupuesto debe ser mayor a cero y los textos no pueden contener solo espacios.
 * @change    1.0.1 - 2026-10-25 - Fernando Luis Gil Correa - Corrección de la normalización de archivos y de las validaciones de negocio.
 */
abstract class TenderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:150',
            'description' => 'required|string|max:400',
            'currency' => 'required|string|max:3|in:COP,USD,EUR',
            'budget' => 'required|numeric|min:0.01|max:9999999999999.99',
            'activity_id' => 'required|integer',
            'start_date' => 'required|date',
            'start_time' => 'required|time',
            'close_date' => 'required|date',
            'close_time' => 'required|time',
            'status' => 'nullable|string|max:20|in:draft,published,closed',
        ];
    }

    /**
     * Normaliza los archivos subidos en la petición a una lista.
     *
     * El campo de la petición se llama "documents". PHP entrega los archivos
     * múltiples como arreglos paralelos, así que se reorganizan en una lista
     * de archivos con la misma estructura.
     */
    public function uploads(): array
    {
        $files = $_FILES['documents'] ?? [];

        if (!isset($files['name'])) {
            return [];
        }

        if (!is_array($files['name'])) {
            return [$files];
        }

        $list = [];

        foreach (array_keys($files['name']) as $index) {
            $list[] = [
                'name' => $files['name'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'error' => $files['error'][$index],
                'size' => $files['size'][$index],
            ];
        }

        return $list;
    }

    protected function after(): array
    {
        $errors = [];

        /*
         * Los textos obligatorios no pueden estar compuestos solo por espacios:
         * la regla required solo descarta la cadena vacía, así que el control
         * del contenido va aquí.
         */
        foreach (['subject', 'description'] as $field) {
            $value = $this->body($field);

            if (is_string($value) && trim($value) === '') {
                $errors[$field] = ["The {$field} field cannot contain only spaces."];
            }
        }

        /*
         * El presupuesto se guarda en una columna decimal(15,2), así que
         * admite como máximo dos decimales. La regla numeric acepta
         * cualquier cantidad de decimales, de modo que el control del formato
         * va aquí.
         */
        $budget = $this->budgetValue();

        if ($budget !== null && preg_match('/^\d+(\.\d{3,})$/', $budget) === 1) {
            $errors['budget'] = ['The budget may only have up to 2 decimal places.'];
        }

        if (!Activity::find($this->body('activity_id'))) {
            $errors['activity_id'] = ['The selected activity is invalid.'];
        }

        if (!$this->closureIsAfterStart()) {
            $errors['close_date'] = ['The close date and time must be after the start date and time.'];
        }

        $fileErrors = [];

        foreach ($this->uploads() as $file) {
            $error = TenderDocument::validateUpload($file);

            if ($error !== null) {
                $fileErrors[] = $error;
            }
        }

        if ($fileErrors !== []) {
            $errors['documents'] = $fileErrors;
        }

        return $errors;
    }

    # Retorna el presupuesto como cadena, o null si no es un valor numérico.
    private function budgetValue(): ?string
    {
        $budget = $this->body('budget');

        if (is_string($budget)) {
            return trim($budget);
        }

        if (is_int($budget) || is_float($budget)) {
            return (string) $budget;
        }

        return null;
    }

    protected function closureIsAfterStart(): bool
    {
        $start = strtotime((string) $this->body('start_date') . ' ' . (string) $this->body('start_time'));
        $close = strtotime((string) $this->body('close_date') . ' ' . (string) $this->body('close_time'));

        if ($start === false || $close === false) {
            return true;
        }

        return $close > $start;
    }
}
