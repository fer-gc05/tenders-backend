<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de la tabla tender_documents(documentos de licitaciones-ofertas).
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.2
 * @since     2026-10-24
 * @updated   2026-10-25
 * @package   App\Models
 *
 * @change    1.0.2 - 2026-10-25 - Fernando Luis Gil Correa - Corrección del límite de tamaño, de la clave del archivo temporal y de los metodos de ruta y eliminacion.
 * @change    1.0.1 - 2026-10-24 - Fernando Luis Gil Correa - Implementacion de metodos auxiliares para menejo de archivos.
 */
class TenderDocument extends Model
{
    # Tamaño maximo permitido(10MB)
    public const MAX_SIZE_BYTES = 10485760;

    # Tipos aceptados
    protected const ALLOWED_MIMES = [
        'application/pdf',
        'application/zip',
        'application/x-zip-compressed'
    ];

    protected $table = 'tender_documents';

    protected $fillable = [
        'tender_id',
        'title',
        'file_path'
    ];

    protected $casts = [
        'tender_id' => 'integer',
    ];

    # Relación con la tabla tenders(licitaciones-ofertas).
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    /**
     * Valida un archivo subido y retorna el mensaje de error en ingles, o
     * null cuando el archivo es valido.
     *
     * Si el archivo no trae codigo de error se asume UPLOAD_ERR_NO_FILE (4).
     */
    public static function validateUpload(array $file): ?string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        /*
         * Los errores de subida que se contemplan son:
         * UPLOAD_ERR_OK = 0, UPLOAD_ERR_INI_SIZE = 1, UPLOAD_ERR_FORM_SIZE = 2,
         * UPLOAD_ERR_PARTIAL = 3 y UPLOAD_ERR_NO_FILE = 4.
         */
        if ($error !== UPLOAD_ERR_OK) {
            return match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The document exceeds the maximum allowed size of 10 MB.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                default => 'The document failed upload.'
            };
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0) {
            return 'The document is empty.';
        }

        if ($size > self::MAX_SIZE_BYTES) {
            return 'The document exceeds the maximum allowed size of 10 MB.';
        }

        /*
         * El tipo se comprueba sobre el contenido real del archivo, nunca
         * sobre el tipo que declara el cliente.
         */
        if (!in_array(self::detectMime((string) $file['tmp_name']), self::ALLOWED_MIMES, true)) {
            return 'The document must be a PDF or ZIP file.';
        }

        return null;
    }

    /**
     * Detecta el tipo de archivo a partir de su ruta.
     *
     * finfo (File Information) es la extensión nativa de PHP que inspecciona
     * el contenido real del archivo y determina su tipo MIME.
     */
    public static function detectMime(string $route): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return null;
        }

        $mime = finfo_file($finfo, $route);
        finfo_close($finfo);

        return is_string($mime) ? $mime : null;
    }

    # Devuelve la extensión de un archivo a partir de su tipo MIME.
    public static function extensionFor(?string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'application/zip', 'application/x-zip-compressed' => 'zip',
            default => 'bin'
        };
    }

    /**
     * Almacena un archivo subido en el servidor.
     *
     * El nombre que envia el cliente nunca se usa en el sistema de archivos,
     * solo se conserva como titulo del documento. El archivo se guarda con un
     * nombre aleatorio y con la extension del tipo real detectado.
     */
    public static function storeUpload(array $file, Tender $tender): self
    {
        $extension = self::extensionFor(self::detectMime((string) $file['tmp_name']));
        $folder = 'uploads/' . date('Y/m');
        $directory = self::storagePath($folder);

        if (!is_dir($directory)) {
            mkdir($directory, 0750, true);
        }

        /*
         * Genera un nombre único de 32 caracteres hexadecimales.
         * random_bytes(16): genera 16 bytes aleatorios criptográficamente seguros.
         * bin2hex(): convierte los bytes binarios a una cadena hexadecimal de 32 caracteres.
         */
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $directory . '/' . $name;

        /*
         * Mueve el archivo cargado desde el directorio temporal del servidor
         * ($file['tmp_name']) hacia la ruta de destino final ($destination),
         * validando previamente que sea una subida HTTP POST válida.
         */
        move_uploaded_file((string) $file['tmp_name'], $destination);

        return static::create([
            'tender_id' => $tender->id,
            'title' => mb_substr((string) $file['name'], 0, 100),
            'file_path' => $folder . '/' . $name
        ]);
    }

    /**
     * Retorna la ruta absoluta de un archivo en el servidor.
     *
     * realpath() resuelve enlaces simbólicos y referencias relativas ('..', '.').
     */
    public static function absolutePath(string $relativePath): ?string
    {
        # Obtiene y resuelve la ruta absoluta real del directorio base de almacenamiento.
        $base = realpath(self::storagePath());

        # Si el directorio base no existe en el sistema de archivos, retorna null.
        if ($base === false) {
            return null;
        }

        /*
         * Resuelve la ruta absoluta real del archivo solicitado.
         * Si el archivo no existe o la ruta es inválida, realpath() retorna false.
         */
        $absolute = realpath(self::storagePath($relativePath));

        if ($absolute === false) {
            return null;
        }

        /*
         * Control de Seguridad (Anti Path Traversal):
         * Verifica estrictamente que la ruta resuelta comience con el prefijo del directorio base.
         * Esto evita que un usuario intente acceder a archivos fuera del storage usando '../'.
         */
        if (!str_starts_with($absolute, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $absolute;
    }

    # Elimina un archivo del servidor.
    public static function deleteFile(string $relativeRoute): void
    {
        $absolute = self::absolutePath($relativeRoute);

        if ($absolute !== null && is_file($absolute)) {
            unlink($absolute);
        }
    }

    /**
     * Retorna la ruta absoluta dentro del directorio de almacenamiento(storage).
     *
     * Define la ruta absoluta base del directorio 'storage' navegando 2 niveles
     * hacia arriba desde el directorio actual.
     */
    public static function storagePath(string $relativePath = ''): string
    {
        $base = dirname(__DIR__, 2) . '/storage';

        /*
         * Si no se especifica una ruta relativa, retorna la ruta base del storage.
         * En caso contrario, concatena la ruta base con la relativa, removiendo
         * barras iniciales sobrantes (ltrim) para evitar rutas con '//'.
         */
        return $relativePath === '' ? $base : $base . '/' . ltrim($relativePath, '/');
    }
}