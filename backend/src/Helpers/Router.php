<?php

namespace App\Helpers;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => []
    ];

    /**
     * Enregistrer une route GET
     */
    public function get(string $pattern, callable $handler): void
    {
        $this->routes['GET'][$pattern] = $handler;
    }

    /**
     * Enregistrer une route POST
     */
    public function post(string $pattern, callable $handler): void
    {
        $this->routes['POST'][$pattern] = $handler;
    }

    /**
     * Enregistrer une route PUT
     */
    public function put(string $pattern, callable $handler): void
    {
        $this->routes['PUT'][$pattern] = $handler;
    }

    /**
     * Enregistrer une route DELETE
     */
    public function delete(string $pattern, callable $handler): void
    {
        $this->routes['DELETE'][$pattern] = $handler;
    }

    /**
     * Gérer la requête courante
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = trim($uri, '/');

        // Récupérer les routes pour la méthode HTTP
        $methodRoutes = $this->routes[$method] ?? [];

        // Essayer de matcher la route
        foreach ($methodRoutes as $pattern => $handler) {
            $regex = $this->convertPatternToRegex($pattern);

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); // Supprimer le match complet
                call_user_func_array($handler, $matches);
                return;
            }
        }

        // Aucune route trouvée
        http_response_code(404);
        echo json_encode([
            'error' => 'Route non trouvée',
            'path' => '/' . $uri
        ]);
    }

    /**
     * Convertir un pattern de route en regex
     */
    private function convertPatternToRegex(string $pattern): string
    {
        // Échapper les slashes
        $pattern = str_replace('/', '\/', $pattern);

        // Remplacer {id} par un groupe de capture numérique
        $pattern = preg_replace('/\{id\}/', '(\d+)', $pattern);

        // Remplacer {param} par un groupe de capture alphanumérique
        $pattern = preg_replace('/\{(\w+)\}/', '([^\/]+)', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Enregistrer un groupe de routes avec un préfixe
     */
    public function group(string $prefix, callable $callback): void
    {
        $originalRoutes = $this->routes;

        // Exécuter le callback qui enregistre les routes
        $callback($this);

        // Ajouter le préfixe à toutes les nouvelles routes
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            $newRoutes = array_diff_key($this->routes[$method], $originalRoutes[$method]);

            foreach ($newRoutes as $pattern => $handler) {
                unset($this->routes[$method][$pattern]);

                // Gérer le cas où le pattern est vide
                if (trim($pattern, '/') === '') {
                    $this->routes[$method][$prefix] = $handler;
                } else {
                    $this->routes[$method][$prefix . '/' . trim($pattern, '/')] = $handler;
                }
            }
        }
    }
}
