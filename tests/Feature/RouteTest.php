<?php

namespace Tests\Feature;

use Illuminate\Routing\Router;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RequestHelper;

    private $apiExceptions = [
        'ProcessMaker\Http\Controllers\TestStatusController@testAcknowledgement',
    ];

    private $webExceptions = [
    ];

    /**
     * This this does some basic checks to make sure we converted routes
     * to the correct class-based routes as part of the Laravel 8 upgrade
     */
    public function testIndexRoute()
    {
        $router = app()->make(Router::class);

        $routes = collect($router->getRoutes())->map(function ($route) use ($router) {
            return [
                'action' => $route->getActionName(),
                'type' => in_array('api', $router->gatherRouteMiddleware($route)) ? 'api' : 'web',
            ];
        });

        foreach ($routes as $route) {
            $action = $route['action'];

            if ($action == 'Closure') {
                continue;
            }

            $method = explode('@', $action);

            // Make sure the method exists in the class
            $this->assertTrue(method_exists($method[0], $method[1]), $method[0] . ' does not have method ' . $method[1]);

            // Make sure we are referencing the correct api or web version of a class, since they often have the same name
            if ($route['type'] == 'api') {
                if (!in_array($action, $this->apiExceptions)) {
                    $this->assertStringContainsString('\\Api\\', $method[0]);
                }
            } else {
                if (!in_array($action, $this->webExceptions)) {
                    $this->assertStringNotContainsString('\\Api\\', $method[0]);
                }
            }
        }
    }
}
