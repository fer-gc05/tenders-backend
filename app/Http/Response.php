<?php

namespace App\Http;

/**
 * Respuesta HTTP.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-22
 * @package App\Http
 */
class Response
{
    # Constructor de la clase Response.
    public function __construct(
    private mixed $data, # Datos de la respuesta.
    private int $status = 200, # Codigo de estado HTTP.
    private array $headers = [], # Headers de la respuesta.
    )
    {}

    # Crea una respuesta JSON.
    public static function json(mixed $data, int $status = 200): self
    {
        return new self($data, $status);
    }

    # Envia la respuesta al cliente.
    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }
}
