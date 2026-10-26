<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Respuesta HTTP.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.2.0
 * @since     2026-10-22
 * @updated   2026-10-26
 * @package   App\Http
 *
 * @change    1.2.0 - 2026-10-26 - Fernando Luis Gil Correa - Headers CORS en
 *            todas las respuestas y respuesta 204 sin cuerpo.
 * @change    1.1.0 - 2026-10-24 - Fernando Luis Gil Correa - Headers
 *            personalizados en las respuestas JSON y codificación que falla
 *            de forma explícita con datos no serializables.
 */
class Response
{
    # Constructor de la clase Response.
    public function __construct(
    private mixed $data, # Datos de la respuesta.
    private int $status = 200, # Código de estado HTTP.
    private array $headers = [], # Headers de la respuesta.
    )
    {}

    # Crea una respuesta JSON.
    #
    # Los headers adicionales se reciben como un mapa nombre => valor. Se
    # usan, por ejemplo, para indicar los métodos permitidos en un 405
    # (header Allow, exigido por el estándar HTTP) o la ubicación del recurso
    # creado en un 201 (header Location).
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        return new self($data, $status, $headers);
    }

    # Crea una respuesta sin contenido (204).
    #
    # El estándar HTTP exige que la respuesta 204 no tenga cuerpo, así que
    # no se envía ni el tipo de contenido ni los datos.
    public static function noContent(): self
    {
        return new self(null, 204);
    }

    # Envia la respuesta al cliente.
    public function send(): void
    {
        http_response_code($this->status);

        # Los headers CORS acompañan a todas las respuestas, no solo al
        # preflight: el navegador los necesita también en la respuesta real.
        foreach (Cors::headers() as $name => $value) {
            header($name . ': ' . $value);
        }

        if ($this->status === 204) {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        # JSON_THROW_ON_ERROR convierte un dato no serializable (por ejemplo,
        # texto con UTF-8 inválido) en una JsonException en lugar de devolver
        # false y enviar un cuerpo vacío con un código 200. La excepción la
        # gestiona el manejador global de errores como un 500.
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
