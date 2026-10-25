<?php

declare(strict_types=1);

namespace App\Views;

use App\Models\TenderDocument;

/**
 * Serializacion de documentos.
 * 
 * @author    Fernando Luis Gil Correa
 * @version   1.0.1
 * @since     2026-10-24
 * @updated   2026-10-25
 * @package   App\Views
 *
 * @change    1.0.1 - 2026-10-25 - Fernando Luis Gil Correa - Corrección de la ruta de descarga del documento.
 */
class DocumentView
{
    public static function make(TenderDocument $document): array
    {
        $route = TenderDocument::absolutePath((string) $document->file_path);

        return [
            'id' => $document->id,
            'tender_id' => $document->tender_id,
            'title' => $document->title,
            'extension' => pathinfo((string) $document->file_path, PATHINFO_EXTENSION),
            'size' => $route !== null && is_file($route) ? filesize($route) : null,
            'download_url' => '/api/v1/tenders/' . $document->tender_id . '/documents/' . $document->id,
            'created_at' => $document->created_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String()
        ];
    }

    public static function collection(iterable $documents): array
    {
        $data = [];

        foreach ($documents as $document) {
            $data[] = self::make($document);
        }

        return $data;

    }

}