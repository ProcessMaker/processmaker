<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Test edit data
 */
class DuplicatedRoutesTest extends TestCase
{
    /**
     * Verify the magic variables for a valid request token
     */
    public function testRoutesCacheGeneration()
    {
        $routes = Route::getRoutes();

        $routeNames = [];

        foreach ($routes as $route) {
            if ($route->getName() !== null) {
                $routeNames[] = $route->getName();
            }
        }

        $uniqueRouteNames = array_unique($routeNames);

        $this->assertCount(count($uniqueRouteNames), $routeNames, 'There are duplicate route names.');
    }
}
