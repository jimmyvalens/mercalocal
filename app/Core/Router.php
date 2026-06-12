<?php
// =========================================================
// src/Core/Router.php — Enrutador HTTP de la aplicación
// Mapea rutas (URL + método HTTP) a métodos de controladores.
// Soporta parámetros dinámicos en la URL: /business/{id}
// =========================================================
namespace App\Core;

class Router
{
    // Array donde se almacenan todas las rutas registradas
    private $routes = [];

    /**
     * Registra una ruta que responde a peticiones GET.
     *
     * @param string $path     Ruta URL, p.ej. '/business/{id}'
     * @param mixed  $callback Array [Controlador::class, 'método'] o callable
     */
    public function get($path, $callback)
    {
        $this->addRoute('GET', $path, $callback);
    }

    /**
     * Registra una ruta que responde a peticiones POST (envío de formularios).
     */
    public function post($path, $callback)
    {
        $this->addRoute('POST', $path, $callback);
    }

    /**
     * Añade internamente una ruta al array $routes.
     * Convierte los parámetros dinámicos {param} en expresiones regulares.
     * Ejemplo: '/business/{id}' → '/business/(?P<id>[a-zA-Z0-9_-]+)'
     */
    private function addRoute($method, $path, $callback)
    {
        // Transforma {nombre_param} en un grupo de captura nombrado de regex
        $pathRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $path);
        $this->routes[] = [
            'method' => $method,
            'path' => '#^' . $pathRegex . '$#', // La ruta como regex completa
            'callback' => $callback
        ];
    }

    /**
     * Analiza la URL de la petición actual y ejecuta el controlador correspondiente.
     * Si no encuentra ninguna ruta coincidente, devuelve un error 404.
     *
     * @param string $method Método HTTP: 'GET' o 'POST'
     * @param string $uri    URI de la petición (de $_SERVER['REQUEST_URI'])
     */
    public function dispatch($method, $uri)
    {
        // Extraer solo la ruta sin parámetros GET (?foo=bar)
        $parsedUri = parse_url($uri, PHP_URL_PATH);
        if (!$parsedUri) {
            $parsedUri = '/';
        }

        // Si la aplicación está instalada en un subdirectorio,
        // eliminamos ese prefijo de la URL para comparar rutas correctamente
        if (defined('BASE_PATH') && BASE_PATH !== '' && BASE_PATH !== '/') {
            if (strpos($parsedUri, BASE_PATH) === 0) {
                $parsedUri = substr($parsedUri, strlen(BASE_PATH));
            }
        }

        // Garantizar que la ruta siempre comienza con '/'
        if ($parsedUri === '' || $parsedUri === false) {
            $parsedUri = '/';
        }

        // Eliminar la barra final si no es la raíz (evita duplicados de ruta)
        if ($parsedUri !== '/' && substr($parsedUri, -1) === '/') {
            $parsedUri = rtrim($parsedUri, '/');
        }

        // Recorrer las rutas registradas y buscar una que coincida
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['path'], $parsedUri, $matches)) {
                // Extraer solo los parámetros nombrados (keys de tipo string)
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Instanciar el controlador y llamar al método correspondiente
                if (is_array($route['callback'])) {
                    $controller = new $route['callback'][0]();
                    call_user_func_array([$controller, $route['callback'][1]], $params);
                }
                else {
                    // También soporta funciones anónimas como callback
                    call_user_func_array($route['callback'], $params);
                }
                return;
            }
        }

        // Ninguna ruta coincidió → respuesta 404
        http_response_code(404);
        echo "404 Not Found - $method $parsedUri";
    }
}
