<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Solicitud HTTP entrante.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.1.0
 * @since     2026-10-22
 * @updated   2026-10-24
 * @package   App\Http
 *
 * @change    1.1.0 - 2026-10-24 - Fernando Luis Gil Correa - Tipos
 *            estrictos y limpieza de los comentarios redundantes.
 */
class Request
{
    # Constructor de la clase Request.
    public function __construct(
    private string $method, # Método HTTP de la solicitud.
    private string $path, # Ruta de la solicitud.
    private array $query, # Parámetros de la query string.
    private array $body, # Cuerpo de la solicitud.
    private array $headers, # Headers de la solicitud.
    private array $params = [], # Parámetros de ruta.
    )
    {}

    # Captura la solicitud HTTP entrante.
    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';
        $headers = function_exists('getallheaders') ? getallheaders() ?: [] : [];
        $body = [];

        # El cuerpo JSON se decodifica primero; si no es JSON válido, queda
        # vacío y se intenta con el cuerpo de formulario (x-www-form-urlencoded).
        $raw = file_get_contents('php://input');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $body = is_array($decoded) ? $decoded : [];
        }

        if ($body === [] && !empty($_POST)) {
            $body = $_POST;
        }

        return new self($method, $path, $_GET, $body, $headers);
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
