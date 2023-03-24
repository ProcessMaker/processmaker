<?php

namespace Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use ProcessMaker\Jobs\RefreshArtisanCaches;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\Setting;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;
    use CreatesApplication;
    use ArraySubsetAsserts;

    public $withPermissions = false;

    /**
     * Run additional setUps from traits.
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

    public function setUpMockScriptRunners(): void
    {
        config()->set('script-runners.php.runner', 'MockRunner');
        config()->set('script-runners.lua.runner', 'MockRunner');
    }

    /**
     * Calling the real config:cache command reconnects the database
     * and since we're using transactions for our tests, we lose any data
     * saved before the command is run. Instead, mock it here and do what
     * it needs to do for the test to continue.
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
}
