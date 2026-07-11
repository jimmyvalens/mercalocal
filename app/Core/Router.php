<?php

/**
 * =========================================================
 * app/Core/Router.php — Enrutador HTTP de la aplicación
 *
 * Gestiona el registro y despacho de rutas HTTP:
 * · Registra rutas GET y POST
 * · Convierte rutas dinámicas en expresiones regulares
 * · Despacha peticiones al controlador o callback adecuado
 * =========================================================
 */

namespace App\Core;

class Router
{
    // Array donde se almacenan todas las rutas registradas
    private array $routes = [];

    /**
     * Registra una ruta que responde a peticiones GET.
     *
     * @param string $path Ruta URL, p.ej. '/business/{id}'
     * @param array|\Closure $callback Controlador y método [Clase, 'metodo'] o función anónima
     * @return void
     */
    public function get(string $path, $callback): void
    {
        $this->addRoute('GET', $path, $callback);
    }

    /**
     * Registra una ruta que responde a peticiones POST.
     *
     * @param string $path Ruta URL, p.ej. '/business/{id}'
     * @param array|\Closure $callback Controlador y método o función anónima
     * @return void
     */
    public function post(string $path, $callback): void
    {
        $this->addRoute('POST', $path, $callback);
    }

    /**
     * Añade una ruta al array de rutas.
     *
     * @param string $method Método HTTP
     * @param string $path Ruta URL con parámetros dinámicos
     * @param array|\Closure $callback El ejecutor de la ruta
     * @return void
     */
    private function addRoute(string $method, string $path, $callback): void
    {
        $pathRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $path);
        $this->routes[] = [
            'method' => $method,
            'path' => '#^' . $pathRegex . '$#',
            'callback' => $callback
        ];
    }

    /**
     * Analiza la URI y ejecuta el callback correspondiente.
     *
     * @param string $method Método HTTP: 'GET' o 'POST'
     * @param string $uri URI de la petición
     * @return void
     */
    public function dispatch($method, $uri)
    {
        $parsedUri = parse_url($uri, PHP_URL_PATH);
        if (!$parsedUri) {
            $parsedUri = '/';
        }

        if (defined('BASE_PATH') && BASE_PATH !== '' && BASE_PATH !== '/') {
            if (strpos($parsedUri, BASE_PATH) === 0) {
                $parsedUri = substr($parsedUri, strlen(BASE_PATH));
            }
        }

        if ($parsedUri === '' || $parsedUri === false) {
            $parsedUri = '/';
        }

        if ($parsedUri !== '/' && substr($parsedUri, -1) === '/') {
            $parsedUri = rtrim($parsedUri, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['path'], $parsedUri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_array($route['callback'])) {
                    $controller = new $route['callback'][0]();
                    call_user_func_array([$controller, $route['callback'][1]], $params);
                } else {
                    call_user_func_array($route['callback'], $params);
                }
                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found - $method $parsedUri";
    }
}
