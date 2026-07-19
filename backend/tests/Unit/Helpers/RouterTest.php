<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Router;
use PHPUnit\Framework\TestCase;

/**
 * Convention de patterns de ce Router : SANS slash de tête (voir
 * public/index.php, ex. $router->get('modules', ...), $router->group(
 * 'api/modules', ...)) -- dispatch() trim() l'URI entrante des deux côtés
 * avant de la comparer, donc un pattern avec un slash de tête ne matche
 * jamais rien. Les tests suivent la même convention que l'app réelle.
 */
class RouterTest extends TestCase
{
    private function dispatchAndCapture(Router $router, string $method, string $uri): string
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        ob_start();
        $router->dispatch();
        return ob_get_clean();
    }

    public function testStaticRouteMatches(): void
    {
        $router = new Router();
        $router->get('modules', function () {
            echo 'modules-list';
        });

        $this->assertSame('modules-list', $this->dispatchAndCapture($router, 'GET', '/modules'));
    }

    public function testNumericIdParameterIsCaptured(): void
    {
        $router = new Router();
        $router->get('modules/{id}', function ($id) {
            echo "module-$id";
        });

        $this->assertSame('module-42', $this->dispatchAndCapture($router, 'GET', '/modules/42'));
    }

    public function testNumericIdParameterRejectsNonDigits(): void
    {
        // {id} se convertit en (\d+) -- "abc" ne doit jamais matcher, sous
        // peine de laisser passer un ID non numérique jusqu'au contrôleur.
        $router = new Router();
        $router->get('modules/{id}', function ($id) {
            echo "module-$id";
        });

        $output = $this->dispatchAndCapture($router, 'GET', '/modules/abc');
        $data = json_decode($output, true);

        // json_encode() échappe les caractères non-ASCII (é plutôt que
        // 'é' littéral) -- comparer sur le JSON décodé plutôt que sur la
        // chaîne brute, pour ne pas dépendre de cet échappement.
        $this->assertSame('Route non trouvée', $data['error']);
    }

    public function testGenericParameterCapturesNonNumericSlug(): void
    {
        $router = new Router();
        $router->get('theme/{slug}', function ($slug) {
            echo "theme-$slug";
        });

        $this->assertSame('theme-dark-mode', $this->dispatchAndCapture($router, 'GET', '/theme/dark-mode'));
    }

    public function testUnmatchedRouteReturns404Body(): void
    {
        $router = new Router();
        $router->get('modules', function () {
            echo 'modules-list';
        });

        $output = $this->dispatchAndCapture($router, 'GET', '/does-not-exist');
        $data = json_decode($output, true);

        $this->assertSame('Route non trouvée', $data['error']);
        $this->assertSame('/does-not-exist', $data['path']);
    }

    public function testWrongHttpMethodDoesNotMatch(): void
    {
        $router = new Router();
        $router->get('modules', function () {
            echo 'modules-list';
        });

        $output = $this->dispatchAndCapture($router, 'POST', '/modules');
        $data = json_decode($output, true);

        $this->assertSame('Route non trouvée', $data['error']);
    }

    public function testGroupPrefixesRoutes(): void
    {
        $router = new Router();
        $router->group('api', function (Router $r) {
            $r->get('ping', function () {
                echo 'pong';
            });
        });

        $this->assertSame('pong', $this->dispatchAndCapture($router, 'GET', '/api/ping'));
    }

    public function testGroupWithEmptyPatternUsesPrefixAlone(): void
    {
        $router = new Router();
        $router->group('modules', function (Router $r) {
            $r->get('', function () {
                echo 'modules-root';
            });
        });

        $this->assertSame('modules-root', $this->dispatchAndCapture($router, 'GET', '/modules'));
    }
}
