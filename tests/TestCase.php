<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use DB;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;
    use CreatesApplication;
    use ArraySubsetAsserts;

    public $withPermissions = false;

    /**
     * Run additional setUps from traits.
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        foreach (get_class_methods($this) as $method) {
            $imethod = strtolower($method);
            if (strpos($imethod, 'setup') === 0 && $imethod !== 'setup') {
                $this->$method();
            }
        }
    }

    public static function setUpMockScriptRunners(): void
    {
        config()->set("script-runners.php.runner", 'MockRunner');
        config()->set("script-runners.lua.runner", 'MockRunner');
    }

    /**
     * Run additional tearDowns from traits.
     *
     */
    protected function tearDown(): void
    {
        $this->cleanDatabaseIfTransactionsNotUsed();
        parent::tearDown();
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
            Artisan::call('passport:install');
        }
    }

    /**
     * Connections transacts
     *
     * @return array
     */
    protected function connectionsToTransact()
    {
        return ['processmaker', 'data'];
    }

    /**
     * If we disable transactions, we must reset the db (the slow way) between tests
     *
     * @return void
     */
    private function cleanDatabaseIfTransactionsNotUsed()
    {
        if (empty($this->connectionsToTransact())) {
            testLog("Restoring from mysqldump after " . $this->getName());
            $databaseHelper = new DatabaseHelper();
            $databaseHelper->replaceCurrentDatabase();
        }
    }
}
