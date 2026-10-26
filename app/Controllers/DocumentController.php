<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Controller;
use App\Http\Cors;
use App\Http\Request;
use App\Http\Response;
use App\Models\Tender;
use App\Models\TenderDocument;

/**
 * Controlador de los documentos de una licitación.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.2.0
 * @since     2026-10-25
 * @updated   2026-10-26
 * @package   App\Controllers
 *
 * @change    1.2.0 - 2026-10-26 - Fernando Luis Gil Correa - Headers CORS en la descarga del archivo del documento.
 * @change    1.1.0 - 2026-10-26 - Fernando Luis Gil Correa - Eliminación de un documento, con la condición de conservar al menos uno por licitación.
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

        # La respuesta binary no pasa por Response, así que los headers CORS
        # se emiten aqui.
        Cors::apply();

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($route));
        header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');

        readfile($route);
        exit;

    }

    /**
     * Elimina un documento de una licitación.
     *
     * La licitación debe conservar al menos un documento: por eso, cuando el
     * documento es el único que tiene, se responde 409 y no se elimina nada.
     */
    public function destroy(Request $request): Response
    {
        $tender = Tender::find((int) $request->param('id'));

        if ($tender === null) {
            return Response::json([
                'error' => 'Tender not found.'
            ], 404);
        }

        $document = TenderDocument::where('tender_id', $tender->id)
            ->find((int) $request->param('documentId'));

        if ($document === null) {
            return Response::json([
                'error' => 'Document not found.'
            ], 404);
        }

        $remaining = TenderDocument::where('tender_id', $tender->id)->count() - 1;

        if ($remaining < 1) {
            return Response::json([
                'error' => 'The tender must keep at least one document.'
            ], 409);
        }

        TenderDocument::deleteFile((string) $document->file_path);
        $document->delete();

        return Response::noContent();
    }

}