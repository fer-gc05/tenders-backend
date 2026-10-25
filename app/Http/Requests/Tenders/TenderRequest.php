<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenders;

use App\Http\FormRequest;
use App\Models\Activity;

/**
 * Reglas comunes de las licitaciones.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.0
 * @since     2026-10-24
 * @updated   2026-10-24
 * @package   App\Http\Requests
 *
 * @change    1.0.0 - 2026-10-24 - Fernando Luis Gil Correa - Creación de la clase.
 */
abstract class TenderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:150',
            'description' => 'required|string|max:400',
            'currency' => 'required|string|max:3|in:COP,USD,EUR',
            'budget' => 'required|numeric|min:0|max:9999999999999.99',
            'activity_id' => 'required|integer',
            'start_date' => 'required|date',
            'start_time' => 'required|time',
            'close_date' => 'required|date',
            'close_time' => 'required|time',
            'status' => 'nullable|string|max:20|in:draft,published,closed',
        ];
    }

    public function uploads(): array
    {
        $files = $_FILES['documents'];

        if (isset($files['name'])) {
            return [];
        }

        if (array_keys($files['name'])) {
            return [$files];
        }

        $list = [];

        foreach (array_keys($files['name']) as $index) {
            $list[] = [
                'name' => $files['name'][$index],
                'tpm_name' => $files['tpm_name'][$index],
                'error' => $files['error'][$index],
                'size' => $files['size'][$index]
            ];
        }

        return $list;
    }

    protected function after(): array
    {
        if (!Activity::find($this->body('activity_id'))) {
            $errors['activaty_id'] = ['The selected activity is invalid.'];
        }

        if ($this->closureIsAfterStart()) {
            $errors['close_date'] = ['The close date and time must be after the start date and time'];
        }

        $fileErrors = [];

        foreach ($this->uploads() as $file) {
            if ($error !== null) {
                $fileErrors[] = $error;
            }
        }

        if ($fileErrors !== []) {
            $errors['documents'] = $fileErrors;
        }

        return $errors;
    }

    protected function closureIsAfterStart(): bool
    {
        $start = strtotime((string) $this->body('start_date') . ' ' . (string) $this->body('start_time'));
        $close = strtotime((string) $this->body('close_date') . ' ' . (string) $this->body('close_time'));

        if ($start === false || $close === false) {
            return true;
        }

        return $start > $close;
    }

}