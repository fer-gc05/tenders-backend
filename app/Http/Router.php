<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Enrutador de la aplicación.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.2.0
 * @since     2026-10-22
 * @updated   2026-10-26
 * @package   App\Http
 *
 * @change    1.2.0 - 2026-10-26 - Fernando Luis Gil Correa - Las peticiones
 *            OPTIONS (preflight) se responden con 204 y los headers CORS.
 * @change    1.1.0 - 2026-10-24 - Fernando Luis Gil Correa - Header Allow
 *            en las respuestas 405, con los métodos registrados para la ruta.
 */
class Router
{
    private array $routes = []; # Rutas registradas.

    # Constructor de la clase Router.
    public function __construct(private Request $request)
    {
    }

    # Registra una ruta GET.
    public function get(string $path, callable|array $action): void
    {
        $this->addRoute('GET', $path, $action);
    }

    # Registra una ruta POST.
    public function post(string $path, callable|array $action): void
    {
        $this->addRoute('POST', $path, $action);
    }

    # Registra una ruta PUT.
    public function put(string $path, callable|array $action): void
    {
        $this->addRoute('PUT', $path, $action);
    }

    # Registra una ruta PATCH.
    public function patch(string $path, callable|array $action): void
    {
        $this->addRoute('PATCH', $path, $action);
    }

    # Registra una ruta DELETE.
    public function delete(string $path, callable|array $action): void
    {
        $this->addRoute('DELETE', $path, $action);
    }

    # Despacha la solicitud actual hacia la acción correspondiente.
    public function dispatch(): void
    {
        $allowed = []; # Métodos registrados para la ruta solicitada.

        /*
         * Las peticiones OPTIONS son el preflight que el navegador envía antes
         * de una petición cross-origin. Se responden con 204 y los headers
         * CORS, sin ejecutar ninguna acción de la ruta.
         */
        if ($this->request->method() === 'OPTIONS') {
            Response::noContent()->send();
            return;
        }

        foreach ($this->routes as [$method, $pattern, $action]) {
            $params = $this->match($pattern, $this->request->path());

            if ($params === null) {
                continue;
            }

            $allowed[] = $method;

            if ($method !== $this->request->method()) {
                continue;
            }

            $this->resolve($action, $this->request->withParams($params))->send();
            return;
        }

        # Cuando la ruta existe pero el método no, el estándar HTTP exige un
        # 405 con el header Allow, que lista los métodos registrados. Así el
        # cliente sabe qué verbos puede usar sobre ese recurso.
        if ($allowed !== []) {
            Response::json(
                ['error' => 'Method not allowed.'],
                405,
                ['Allow' => implode(', ', array_unique($allowed))]
            )->send();
            return;
        }

        Response::json(['error' => 'Route not found.'], 404)->send();
    }

    # Agrega una ruta al registro.
    private function addRoute(string $method, string $path, callable|array $action): void
    {
        $this->routes[] = [$method, $path, $action];
    }

    # Compara un patron de ruta contra la ruta de la solicitud.
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_]\w*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    # Ejecuta la accion de la ruta y retorna la respuesta.
    private function resolve(callable|array $action, Request $request): Response
    {
        if (is_array($action)) {
            [$class, $method] = $action;

            # Si el metodo pide un FormRequest, se instancia y valida antes.
            $parameter = (new \ReflectionMethod($class, $method))->getParameters()[0] ?? null;
            $type = $parameter?->getType();

            if ($type instanceof \ReflectionNamedType && is_subclass_of($type->getName(), FormRequest::class)) {
                $request = $type->getName()::from($request);
                $request->validate();
            }

            $action = [new $class(), $method];
        }

        $result = $action($request);

        return $result instanceof Response ? $result : Response::json($result);
    }
}
