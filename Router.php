<?php

namespace MVC;

class Router
{
    public array $getRoutes = [];
    public array $postRoutes = [];

    public function get(string $url, callable $fn): void
    {
        $this->getRoutes[$url] = $fn;
    }

    public function post(string $url, callable $fn): void
    {
        $this->postRoutes[$url] = $fn;
    }

    public function comprobarRutas()
    {
        // Solución ideal para php -S localhost:3000 y servidores reales
        $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $fn = $this->getRoutes[$currentUrl] ?? null;
        } else {
            $fn = $this->postRoutes[$currentUrl] ?? null;
        }

        if ($fn) {
            // Llama a la función asignada en el controlador
            call_user_func($fn, $this);
        } else {
            http_response_code(404);
            echo "Página No Encontrada o Ruta no válida";
        }
    }

    public function render(string $view, array $datos = [], string $layout = 'layout'): void
    {
        foreach ($datos as $key => $value) {
            $$key = $value;
        }

        ob_start();
        include __DIR__ . "/views/$view.php";
        $contenido = ob_get_clean();
        include __DIR__ . "/views/$layout.php";
    }
}
