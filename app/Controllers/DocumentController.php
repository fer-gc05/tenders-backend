<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Models\TenderDocument;

/**
 * Controlador de los documentos de una licitación.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.1
 * @since     2026-10-25
 * @updated   2026-10-25
 * @package   App\Controllers
 *
 * @change    1.0.1 - 2026-10-25 - Fernando Luis Gil Correa - Validación de la existencia del archivo antes de enviarlo.
 */
class DocumentController extends Controller
{
    public function download(Request $request): Response
    {
        $document = TenderDocument::where('tender_id', (int) $request->param('id'))
            ->find((int) $request->param('documentId'));

        if ($document === null) {
            return Response::json([
                'error' => 'Document not found.'
            ], 404);
        }

        $route = TenderDocument::absolutePath((string) $document->file_path);

        if ($route === null || !is_file($route)) {
            return Response::json([
                'error' => 'The document file is missing.'
            ], 404);
        }

        $name = (string) $document->title;
        $mime = TenderDocument::detectMime($route) ?: 'application/octet-stream';
        $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'document';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($route));
        header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');

        readfile($route);
        exit;

    }

}