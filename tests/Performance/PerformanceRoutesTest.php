<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\PerformanceReportTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 */
class PerformanceRoutesTest extends TestCase
{
    use WithFaker;
    use RequestHelper;
    use ResourceAssertionsTrait;
    use PerformanceReportTrait;

    /**
     * Time unit base for the performce tests
     *
     * @param integer $times
     *
     * @return float
     */
    private function calculateUnitTime($times = 100)
    {
        $model = Group::class;
        $t = microtime(true);
        factory($model, $times)->create();
        $baseTime = microtime(true) - $t;
        $model::getQuery()->delete();
        return $baseTime;
    }

    // Endpoints to be tested
    private $endpoints = [
        ['groups.index', []],
        ['users.index', []],
        ['auth-clients.index', []],
        ['customize-ui.edit', []],
        ['admin.index', []],
        ['environment-variables.index', []],
        ['screens.index', []],
        ['screens.import', []],
        ['scripts.index', []],
        ['process-categories.index', []],
        ['processes.index', []],
        ['processes.import', []],
        ['processes.create', []],
        ['about.index', []],
        ['profile.edit', []],
        ['home', []],
        ['requests.index', []],
        ['tasks.index', []],
        ['notifications.index', []],
        ['login', []],
        ['logout', []],
        ['password.request', []],
        ['password-success', []],
        ['error.unavailable', []],
    ];

    // High values ​​improve measurement accuracy and reduce the effect of database caches
    private $repetitions = 50;
    // Inicial size of database
    private $dbSize = 50;
    const MIN_ROUTE_SPEED = 0.1;
    const ACCEPTABLE_ROUTE_SPEED = 1;
    const DESIRABLE_ROUTE_SPEED = 11;

    public function RoutesListProvider()
    {
        file_exists('coverage') ?: mkdir('coverage');
        return $this->endpoints;
    }

    /**
     * Test routes speed
     *
     * @dataProvider RoutesListProvider
     */
    public function testRoutesSpeed($route, $params)
    {
        $this->user = factory(User::class)->create(['is_administrator' => true]);
        factory(Comment::class, $this->dbSize)->create();
        $this->actingAs($this->user);
        $this->withoutExceptionHandling();

        $baseTime = $this->calculateUnitTime();

        // Test endpoint
        $path = route($route, $params);
        $fn = (substr($route, 0, 4) === 'api.') ? 'apiCall' : 'webCall';
        $times = $this->repetitions;
        $t = microtime(true);
        for ($i = 0;$i < $times;$i++) {
            $this->$fn('GET', $path);
        }
        $time = microtime(true) - $t;

        $requestsPerSecond = round($times / $time * 10) / 10;
        $speed = $times / ($time / $baseTime);
        $this->addMeasurement('routes', [
            'route' => $route,
            'params' => $params,
            'color' => $speed < self::MIN_ROUTE_SPEED ? 'table-danger' :
                ($speed < self::ACCEPTABLE_ROUTE_SPEED ? 'table-warning' :
                ($speed >= self::DESIRABLE_ROUTE_SPEED ? 'table-success' : '')),
            'speed' => round($speed * 10) / 10,
            'requestsPerSecond' => $requestsPerSecond,
            'time' => round($time / $times * 100000) / 100,
        ]);
        $this->writeReport('routes', 'coverage/routes_performance.html', 'routes.performance.template.php');
        $this->assertGreaterThanOrEqual(self::MIN_ROUTE_SPEED, $speed, "Slow route response [$route]\n             Speed ~ $requestsPerSecond [reqs/sec]");
    }
}
