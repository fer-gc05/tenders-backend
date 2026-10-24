<?php

namespace App\Http;

/**
 * Enrutador de la aplicación.
 *
 * @author Fernando Luis Gil Correa
 * @version 1.0.0
 * @since 2026-10-22
 * @package App\Http
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

    # Despacha la solicitud actual hacia la accion correspondiente.
    public function dispatch(): void
    {
        $pathMatched = false;

        foreach ($this->routes as [$method, $pattern, $action]) {
            $params = $this->match($pattern, $this->request->path());

            if ($params === null) {
                continue;
            }

            $pathMatched = true;

            if ($method !== $this->request->method()) {
                continue;
            }

            $this->resolve($action, $this->request->withParams($params))->send();
            return;
        }

        $pathMatched
            ? Response::json(['error' => 'Method not allowed.'], 405)->send()
            : Response::json(['error' => 'Route not found.'], 404)->send();
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
