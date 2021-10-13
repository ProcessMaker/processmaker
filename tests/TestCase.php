<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ProcessCategory;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;
    use CreatesApplication;

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

        $q = \DB::table('INFORMATION_SCHEMA.TABLES')->select('AUTO_INCREMENT')->where('TABLE_SCHEMA', 'test')->where('TABLE_NAME', 'screens');
        $autoIncrement = $q->first()->AUTO_INCREMENT;
        error_log('screens: ' . $autoIncrement);

    }

    /**
     * Run additional tearDowns from traits.
     *
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        foreach (get_class_methods($this) as $method) {
            $imethod = strtolower($method);
            if (strpos($imethod, 'teardown') === 0 && $imethod !== 'teardown') {
                $this->$method();
            }
        }
        //error_log('categories: ' . ProcessCategory::count());
    }

    protected function withPersonalAccessClient()
    {
        //skip
        $this->markTestSkipped('This test is not ready yet.');
        return;
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
}
