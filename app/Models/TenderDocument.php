<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de la tabla tender_documents(documentos de licitaciones-ofertas).
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.1
 * @since 2026-10-24
 * @updated 2026-10-24
 * @package App\Models
 * 
 * @change 1.0.1 - Fernando Luis Gil Correa - Implementacion de metodos auxiliares para menejo de archivos.
 */
class TenderDocument extends Model
{
    # Tamaño maximo permitido(10MB)
    public const MAX_SIZE_BYTES = 1048576;

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

    # Validacion de arrchivos subidos. Retorna error en caso de invalides de archivos.
    public static function validateUpload(array $file): ?string
    {
        /**Extrae el error en caso de no extraerlo usa por defecto el 
         *codigo UPLOAD_ERR_NO_FILE = 4.
         **/
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        /**Si el error que se estrae es distinto a
         *UPLOAD_ERR_OK = 0. Valida con los siguintes posibles errores:
         *UPLOAD_ERR_INI_SIZE = 1, UPLOAD_ERR_FORM_SIZE = 2, UPLOAD_ERR_PARTIAL = 3, UPLOAD_ERR_NO_FILE=4.
         **/
        if ($error !== UPLOAD_ERR_OK) {
            return match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The document exceeds the maximun allowed size of 10 MB.',
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
            return 'The document exceeds the maximun allowed size of 10 MB.';
        }

        if (!in_array(self::detectdMime((string) $file['temp_name']), self::ALLOWED_MIMES, true)) {
            return 'The document must be a PDF or ZIP file';
        }

        return null;
    }

    # Detecta el tipo de archivo a partir de su ruta.
    public static function detectdMime(string $route): ?string
    {
        /** 
         *Crea el recurso finfo para detectar el tipo de archivo.
         *finfo (File Information): extensión nativa de PHP que
         *ayuda a inspeccionar y determinar el tipo MIME
         *(Multipurpose Internet Mail Extensions) y la codificación
         *de un archivo analizando su contenido real.
         */
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return '';
        }

        $mime = finfo_file($finfo, $route);
        finfo_close($finfo);

        return is_string($mime) ? $mime : '';
    }

    # Devuelve la extensión de un archivo a partir de su tipo MIME.
    public static function extensionFor(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'application/zip', 'application/x-zip-compressed' => 'zip',
            default => 'bin'
        };
    }

    # Almacena un archivo subido en el servidor.
    public static function storeUpload(array $file, Tender $tender): self
    {
        $extension = self::extensionFor(self::detectdMime((string) $file['temp_name']));
        $folder = 'uploads/' . date('Y/m');
        $directory = self::storagePath($folder);

        if (!is_dir($directory)) {
            mkdir($directory, 0750, true);
        }

        # Genera un nombre único de 32 caracteres hexadecimales.
        # random_bytes(16): genera 16 bytes aleatorios criptográficamente seguros.
        # bin2hex(): convierte los bytes binarios a una cadena hexadecimal de 32 caracteres.
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $directory . '/' . $name;

        # Mueve el archivo cargado desde el directorio temporal del servidor ($file['temp_name'])
        # hacia la ruta de destino final ($destination), validando previamente que sea una subida HTTP POST válida.
        move_uploaded_file((string) $file['temp_name'], $destination);

        return static::create([
            'tender_id' => $tender->id,
            'title' => mb_substr((string) $file['name'], 0, 100),
            'file_path' => $folder . '/' . $name
        ]);
    }

    # Retorna la ruta absoluta de un archivo en el servidor.
    public static function absolutePath(string $relativePath): ?string
    {
        # Obtiene y resuelve la ruta absoluta real del directorio base de almacenamiento.
        # realpath() resuelve enlaces simbólicos y referencias relativas ('..', '.').
        $base = realpath(self::storegePath());

        # Si el directorio base no existe en el sistema de archivos, retorna null.
        if ($base === false) {
            return null;
        }

        # Resuelve la ruta absoluta real del archivo solicitado.
        # Si el archivo no existe o la ruta es inválida, realpath() retorna false.
        $absolute = realpath(self::storagePath($relativePath));
        if ($absolute === false) {
            return null;
        }

        # Control de Seguridad (Anti Path Traversal):
        # Verifica estrictamente que la ruta resuelta comience con el prefijo del directorio base.
        # Esto evita que un usuario intente acceder a archivos fuera del storage usando '../'.
        if (!str_starts_with($absolute, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $absolute;
    }

    # Elimina un archivo del servidor.
    public static function deleteFile(string $relativeRoute)
    {
        $absolute = realpath(self::storegePath());

        if ($absolute !== null && is_file($absolute)) {
            unlink($absolute);
        }
    }

    #Retorn la ruta absoluta dentro del directorio de almacenamiento(storage)
    public static function storegePath(string $relativePath = ''): string
    {
        # Define la ruta absoluta base del directorio 'storage' navegando 2 niveles hacia arriba desde el directorio actual.
        $base = dirname(__DIR__, 2) . '/storage';

        # Si no se especifica una ruta relativa, retorna la ruta base del storage.
        # En caso contrario, concatena la ruta base con la relativa, removiendo barras iniciales sobrantes (ltrim) para evitar rutas con '//'.
        return $relativePath === '' ? $base : $base . '/' . ltrim($relativePath, '/');
    }
}