<?php

namespace Tests;

use ArrayAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Testing\Constraints\ArraySubset;
use Illuminate\Testing\Exceptions\InvalidArgumentException;
use PDOException;
use PHPUnit\Event\Facade as EventFacade;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Jobs\RefreshArtisanCaches;
use ProcessMaker\Models\Process;
use Tests\TestSeeder;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    public $withPermissions = false;

    protected $skipTeardownPDOException = false;

    protected $seeder = TestSeeder::class;

    private static array $transactionWarnings = [];

    private static $cacheCleared = false;

    /**
     * Run additional setUps from traits.
     */
    protected function setUp(): void
    {
        if ($this->connectionsToTransact() === []) {
            $this->markTestSkipped('Skipping for Laravel 11');
        }

        if (!$this->populateDatabase()) {
            RefreshDatabaseState::$migrated = true;
        }

        $this->skipTeardownPDOException = false;

        parent::setUp();

        Redis::flushall();

        if (!self::$cacheCleared) {
            Artisan::call('optimize:clear');
            self::$cacheCleared = true;
        }

        $this->disableSetContentMiddleware();

        foreach (get_class_methods($this) as $method) {
            $imethod = strtolower($method);
            if (strpos($imethod, 'setup') === 0 && $imethod !== 'setup') {
                $this->$method();
            }
        }
    }

    /**
     * Disable middleware that calls setContent() otherwise we can't use assertViewIs()
     */
    private function disableSetContentMiddleware()
    {
        if (class_exists(\ProcessMaker\Package\SavedSearch\Http\Middleware\InjectJavascript::class)) {
            $this->withoutMiddleware(\ProcessMaker\Package\SavedSearch\Http\Middleware\InjectJavascript::class);
        }

        if (class_exists(\ProcessMaker\Package\ProductAnalytics\Http\Middleware\ProductAnalyticsMiddleware::class)) {
            $this->withoutMiddleware(\ProcessMaker\Package\ProductAnalytics\Http\Middleware\ProductAnalyticsMiddleware::class);
        }
    }

    public function setUpMockScriptRunners(): void
    {
        config()->set('script-runners.php.runner', 'MockRunner');
        config()->set('script-runners.lua.runner', 'MockRunner');
        config()->set('script-runners.php-nayra.runner', 'MockRunner');
    }

    /**
     * Calling the real config:cache command reconnects the database
     * and since we're using transactions for our tests, we lose any data
     * saved before the command is run. Instead, mock it out here.
     */
    public function setUpMockConfigCache(): void
    {
        Bus::fake([
            RefreshArtisanCaches::class,
        ]);
    }

    /**
     * Run additional tearDowns from traits.
     */
    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } catch (PDOException $e) {
            if (!$this->skipTeardownPDOException) {
                throw $e;
            }
        }
        foreach (get_class_methods($this) as $method) {
            $imethod = strtolower($method);
            if (strpos($imethod, 'teardown') === 0 && $imethod !== 'teardown') {
                $this->$method();
            }
        }
    }

    protected function withPersonalAccessClient()
    {
        $clients = app()->make('Laravel\Passport\ClientRepository');
        try {
            $clients->personalAccessClient();
        } catch (\RuntimeException $e) {
            Artisan::call('passport:install --no-interaction');
        }
    }

    /**
     * Create a process from a BPMN file and additional process data in PHP.
     *
     * @param string bpmnFile The `bpmnFile` parameter is a string that represents the path of a BPMN file located in the
     * `tests/Fixtures/` directory.
     * @param array attributes The `attributes` parameter is an array that allows you to pass additional data when
     * creating a process from BPMN. By merging this data with the default data obtained from the BPMN file.
     *
     * @return The function `createProcessFromBPMN` is returning a new instance of the `Process` model that is created
     * using the `factory()` method.
     */
    protected function createProcessFromBPMN(string $bpmnFile, array $attributes = []): Process
    {
        $data = [
            'bpmn' => file_get_contents(base_path($bpmnFile)),
        ];

        return Process::factory()->create(array_merge($data, $attributes));
    }

    /**
     * Creates a Process instance from a JSON file.
     *
     * This method reads the specified JSON file, merges the provided attributes,
     * and creates a new Process instance.
     *
     * @param string $jsonFile The path to the JSON file containing the process definition.
     * @param array $attributes Additional attributes to merge into the process definition.
     * @return Process The created Process instance.
     */
    protected function createProcessFromJSON(string $jsonFile, array $attributes = []): Process
    {
        $payload = json_decode(file_get_contents($jsonFile), true);
        $options = new Options([]);
        $importer = new Importer($payload, $options);
        $importer->previewImport();
        $manifest = $importer->doImport();
        $processId = $manifest[$payload['root']]->log['newId'];
        $process = Process::find($processId);
        $process->update($attributes);

        return $process;
    }

    // Copied from Illuminate/Testing/Assert.php
    public function assertArraySubset($subset, $array, bool $checkForIdentity = false, string $msg = ''): void
    {
        if (!(is_array($subset) || $subset instanceof ArrayAccess)) {
            throw InvalidArgumentException::create(1, 'array or ArrayAccess');
        }

        if (!(is_array($array) || $array instanceof ArrayAccess)) {
            throw InvalidArgumentException::create(2, 'array or ArrayAccess');
        }

        $constraint = new ArraySubset($subset, $checkForIdentity);

        $this->assertThat($array, $constraint, $msg);
    }

    private function populateDatabase() : bool
    {
        return (bool) env('POPULATE_DATABASE', true);
    }
}
