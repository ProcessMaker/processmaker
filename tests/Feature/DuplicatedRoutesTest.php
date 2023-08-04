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
        $duplicated = [];

        foreach ($routes as $route) {
            $routeName = $route->getName();
            if ($routeName !== null) {
                if (in_array($routeName, $routeNames)) {
                    $duplicated[] = $routeName;
                }
                $routeNames[] = $routeName;
            }
        }

        $this->assertCount(
            0,
            $duplicated,
            'There are duplicate route names: ' . implode(', ', $duplicated)
        );
    }
}
