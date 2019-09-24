<?php

namespace Tests\Feature\Api;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use ReflectionObject;
use Tests\Feature\Shared\PerformanceReportTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 * @group process_tests
 */
class PerformanceTest extends TestCase
{
    use WithFaker;
    use RequestHelper;
    use ResourceAssertionsTrait;
    use PerformanceReportTrait;

    // Speed of model Group creation (records/unit_time):
    // u=71.48 σ=22.16 => Min Speed of distribution = 27.16
    // Maximum allowed payload per creation: 2 times the creation
    const MIN_SPEED = 27 / (1 + 2 * 71 / 27);

    private $exceptions = [
        // Hi payload because of password hash
        //'ProcessMaker\Models\User' => 27*6/100,
    ];

    /**
     * List the factories
     *
     * @return array
     */
    public function FactoryListProvider()
    {
        file_exists('coverage') ?: mkdir('coverage');
        $factories = app(EloquentFactory::class);
        $reflection = new ReflectionObject($factories);
        $property = $reflection->getProperty('definitions');
        $property->setAccessible(true);
        $definitions = $property->getValue($factories);

        $baseTime = $this->calculateUnitTime();

        $models = [];
        foreach ($definitions as $model => $definition) {
            $models[] = [$model, $baseTime];
        }
        return $models;
    }

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

    /**
     *
     *
     * @param [type] $model
     * @param [type] $baseCount
     * @param [type] $baseTime
     *
     * @dataProvider FactoryListProvider
     */
    public function testFactories($model, $baseTime)
    {
        $baseCount = $this->getTotalRecords();
        $t = microtime(true);
        $times = 1;
        factory($model, $times)->create();
        $time = microtime(true) - $t;
        $count = $this->getTotalRecords();
        $speed = ($count - $baseCount) / ($time / $baseTime);
        $minSpeed = isset($this->exceptions[$model]) ? $this->exceptions[$model] : self::MIN_SPEED;
        $factorySpeed = $times / ($time / $baseTime);

        $this->addMeasurement('factories', [
            'model' => $model,
            'time' => round($time / $times * 100000) / 100,
            'factorySpeed' => round($factorySpeed * 10) / 10,
            'recordsPerFactory' => round(($count - $baseCount) / $times),
            'speed' => round($speed * 10) / 10,
            'color' => $speed < $minSpeed ? 'table-danger' : 'table-success',
        ]);
        $this->writeReport('factories', 'coverage/factory_performance.html', 'models.performance.template.php');
        $this->assertGreaterThanOrEqual($minSpeed, $speed);
    }

    /**
     * Get total count of records in the databases
     *
     * @return int
     */
    private function getTotalRecords()
    {
        $tables = [];
        foreach (config('database.connections') as $name => $config) {
            $connection = DB::connection($name);
            $list = $connection->getDoctrineSchemaManager()->listTableNames();
            foreach ($list as $table) {
                if (!isset($tables[$table])) {
                    $tables[$table] = $connection->table($table)->count();
                }
            }
        }
        return array_sum($tables);
    }

    // Endpoints to be tested
    private $endpoints = [
        ['l5-swagger.oauth2_callback', []],
        ['passport.tokens.index', []],
        ['passport.clients.index', []],
        ['api.users.index', []],
        ['api.groups.index', []],
        ['api.group_members.index', []],
        ['api.group_members_available.show', []],
        ['api.user_members_available.show', []],
        ['api.environment_variables.index', []],
        ['api.screens.index', []],
        ['api.screen_categories.index', []],
        ['api.scripts.index', []],
        ['api.processes.index', []],
        ['api.processes.start', []],
        ['api.process_categories.index', []],
        ['api.tasks.index', []],
        ['api.requests.index', []],
        ['api.files.index', []],
        ['api.notifications.index', []],
        ['api.task_assignments.index', []],
        ['api.comments.index', []],
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
    private $dbSize = 100;
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
        $this->writeReport('routes', 'coverage/route_performance.html', 'routes.performance.template.php');
        $this->assertGreaterThanOrEqual(self::MIN_ROUTE_SPEED, $speed, "Slow route response [$route]\n             Speed ~ $requestsPerSecond [reqs/sec]");
    }
}
