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
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Jobs\RefreshArtisanCaches;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\TestSeeder;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['processmaker', 'data'];

    public $withPermissions = false;

    protected $seeder = TestSeeder::class;

    private static array $transactionWarnings = [];

    private static $cacheCleared = false;

    private static $currentNonTransactionalTest = null;

    private static $databaseSnapshotFile = null;

    public $dropViews = true;

    public $skipSetupMethods = ['setUpBeforeClass', 'setUpTheTestEnvironment', 'setUpTraits'];

    public $skipTearDownMethods = ['tearDownAfterClass', 'tearDownTheTestEnvironment', 'tearDownAfterClassUsingTestCase'];

    /**
     * Run additional setUps from traits.
     */
    protected function setUp(): void
    {
        $class = get_class($this);

        if (self::$currentNonTransactionalTest && self::$currentNonTransactionalTest !== $class) {
            // The last test to run was non-transactional, so we need to refresh the database
            $this->restoreDatabaseFromSnapshot();
            self::$currentNonTransactionalTest = null;
        }

        if ($this->connectionsToTransact() === []) {
            self::$currentNonTransactionalTest = $class;
        }

        if (!$this->populateDatabase()) {
            RefreshDatabaseState::$migrated = true;
        }

        parent::setUp();

        // Clear Redis cache before running tests
        foreach (['default', 'cache', 'cache_settings'] as $connection) {
            Redis::connection($connection)->flushDb();
        }

        if (!self::$cacheCleared) {
            Artisan::call('optimize:clear');
            self::$cacheCleared = true;
        }

        $this->disableSetContentMiddleware();

        $classMethods = get_class_methods($this);
        foreach (array_diff($classMethods, $this->skipSetupMethods) as $method) {
            $imethod = strtolower($method);
            if (strpos($imethod, 'setup') === 0 && $imethod !== 'setup') {
                $this->$method();
            }
        }

        if (!self::$databaseSnapshotFile) {
            self::$databaseSnapshotFile = $this->takeDatabaseSnapshot();
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
        parent::tearDown();

        $classMethods = get_class_methods($this);
        foreach (array_diff($classMethods, $this->skipTearDownMethods) as $method) {
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

    private function takeDatabaseSnapshot($filename = 'test-db-snapshot.db')
    {
        if (!$this->populateDatabase()) {
            return;
        }

        $snapshotFile = base_path($filename);
        $command = 'mysqldump ' . $this->mysqlConnectionString();
        $command .= ' ' . env('DB_DATABASE') . ' > ' . $snapshotFile;
        exec($command, $output, $return);
        if ($return !== 0) {
            dd("Failed to take database snapshot: $command");
        }

        return $snapshotFile;
    }

    public function restoreDatabaseFromSnapshot($filename = 'test-db-snapshot.db')
    {
        if (!$this->populateDatabase()) {
            return;
        }

        if (!file_exists(base_path($filename))) {
            throw new \Exception("Database snapshot not found: $filename");
        }
        $command = 'mysql ' . $this->mysqlConnectionString();
        $command .= ' ' . env('DB_DATABASE') . ' < ' . base_path($filename);
        if (system($command) === false) {
            dd("Failed to restore database from snapshot: $command");
        }
    }

    private function mysqlConnectionString()
    {
        $user = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        $host = env('DB_HOSTNAME');
        $port = env('DB_PORT');

        $command = '-u ' . $user;
        if (!empty($password)) {
            $command .= ' -p\'' . $password . '\'';
        }
        if (!empty($host)) {
            $command .= ' -h ' . $host;
        }
        if (!empty($port)) {
            $command .= ' -P ' . $port;
        }

        return $command;
    }
}
