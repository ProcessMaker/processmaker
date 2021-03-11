<?php

namespace Tests\Feature\Api;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\ScriptExecutor;
use ReflectionObject;
use Tests\Feature\Shared\PerformanceReportTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * Tests routes related to processes / CRUD related methods
 *
 */
class PerformanceModelsTest extends TestCase
{
    use WithFaker;
    use RequestHelper;
    use ResourceAssertionsTrait;
    use PerformanceReportTrait;

    // Speed of model Group creation (records/unit_time):
    // unit_time = time a single model is created
    // u=71.48 σ=22.16 => Min Speed of distribution = 27.16
    // Maximum allowed payload per creation: 2 times the creation
    const MIN_SPEED = 1;

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
        ScriptExecutor::setTestConfig('php');
        ScriptExecutor::setTestConfig('lua');

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
}
