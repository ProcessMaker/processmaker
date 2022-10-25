<?php

namespace Tests\Feature;

use Illuminate\Routing\Router;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RequestHelper;

    /**
     * This this does some basic checks to make sure we converted routes
     * to the correct class-based routes as part of the Laravel 8 upgrade
     */
    public function testIndexRoute()
    {
        $ethosRoutePath = base_path('vendor/processmaker/package-ellucian-ethos/routes/');
        if (file_exists($ethosRoutePath . 'api.php')) {
            require_once $ethosRoutePath . 'api.php';
            require_once $ethosRoutePath . 'web.php';
        }

        $router = app()->make(Router::class);

        $routes = collect($router->getRoutes())->map(function ($route) use ($router) {
            return [
                'action' => $route->getActionName(),
                'type' => in_array('auth:api', $router->gatherRouteMiddleware($route)) ? 'api' : 'web',
            ];
        });

        foreach ($routes as $route) {
            $action = $route['action'];

            if ($action == 'Closure') {
                continue;
            }

            $method = explode('@', $action);

            if (count($method) === 1) {
                $method[1] = '__invoke';
            }

            // Make sure the method exists in the class
            $this->assertTrue(method_exists($method[0], $method[1]), $method[0] . ' does not have method ' . $method[1]);
        }
    }
}
