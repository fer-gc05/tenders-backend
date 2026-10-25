<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenders;

use App\Models\TenderDocument;

/**
 * Solicitud de actualización de una licitación.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.0
 * @since     2026-10-24
 * @updated   2026-10-24
 * @package   App\Http\Requests
 *
 * @change    1.0.0 - 2026-10-24 - Fernando Luis Gil Correa - Creación de la clase.
 */
class UpdateTenderRequest extends TenderRequest
{
    protected function after(): array
    {
        $errors = parent::after();

        $existing = TenderDocument::where('tender_id', (int) $this->param('id'))->count();
        $news = count($this->uploads());

        if ($existing + $news < 1 && !isset($errors['documents'])) {
            $errors['documents'] = ['At least one document is required.'];
        }
        return $errors;
    }

}