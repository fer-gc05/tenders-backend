<?php

declare(strict_types=1);

namespace App\Views;

use App\Models\TenderDocument;

/**
 * Serializacion de documentos.
 * 
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-24
 * @updated 2026-10-24
 * @package App\Views
 * 
 * @change 1.0.0 - 26-10-24 - Fernando Luis Gil Correa - Creacion de la clase.
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
            'download_url' => 'api/v1/tenders/' . $document->tender_id . '/documents/' . $document->id,
            'created_at' => $document->created_at->toIso8601String(),
            'updated_at' => $document->updated_at->toIso8601String()
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