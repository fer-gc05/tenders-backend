<?php

namespace App\Http;

/**
 * Solicitud HTTP entrante.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-22
 * @package App\Http
 */
class Request
{
    # Constructor de la clase Request.
    public function __construct(
    private string $method, # Metodo HTTP de la solicitud.
    private string $path, # Ruta de la solicitud.
    private array $query, # Parametros de la solicitud.
    private array $body, # Cuerpo de la solicitud.b
    private array $headers, # Headers de la solicitud.
    private array $params = [], # Parametros de la solicitud.
    )
    {}

    # Captura la solicitud HTTP entrante.
    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); # Metodo HTTP de la solicitud.
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; # Ruta de la solicitud.
        $path = rtrim($path, '/') ?: '/'; # Ruta de la solicitud.
        $headers = function_exists('getallheaders') ? getallheaders() ?: []: []; # Headers de la solicitud.
        $body = []; # Cuerpo de la solicitud.

        $raw = file_get_contents('php://input'); # Cuerpo de la solicitud.

        if (is_string($raw) && $raw !== '') { # Si el cuerpo de la solicitud es una cadena y no es vacia.
            $decoded = json_decode($raw, true); # Decodifica el cuerpo de la solicitud.
            $body = is_array($decoded) ? $decoded : []; # Cuerpo de la solicitud.
        }

        if ($body === [] && !empty($_POST)) { # Si no hay JSON, usa el cuerpo de formulario.
            $body = $_POST;
        }

        return new self($method, $path, $_GET, $body, $headers); # Crea una nueva instancia de la clase Request.
    }

    # Retorna el metodo HTTP de la solicitud.
    public function method(): string
    {
        return $this->method;
    }

    # Retorna la ruta de la solicitud.
    public function path(): string
    {
        return $this->path;
    }

    # Retorna un parametro de la query string o todos.
    public function query(?string $key = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? null);
    }

    # Retorna un valor del cuerpo de la solicitud o todo el cuerpo.
    public function body(?string $key = null): mixed
    {
        return $key === null ? $this->body : ($this->body[$key] ?? null);
    }

    # Retorna el valor de un header de la solicitud.
    public function header(string $name): ?string
    {
        $needle = strtolower($name);

        foreach ($this->headers as $header => $value) {
            if (strtolower((string) $header) === $needle) {
                return $value;
            }
        }

        return null;
    }

    # Retorna todos los headers de la solicitud.
    public function headers(): array
    {
        return $this->headers;
    }

    # Retorna los parametros de ruta.
    public function params(): array
    {
        return $this->params;
    }

    # Retorna una copia de la solicitud con los parametros de ruta.
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    # Retorna un parametro de ruta.
    public function param(string $key): mixed
    {
        return $this->params[$key] ?? null;
    }
}
