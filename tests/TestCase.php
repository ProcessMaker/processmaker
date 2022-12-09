<?php

namespace Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\RequestUserPermission;
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

    public static function setUpMockScriptRunners(): void
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
    public static function setUpMockConfigCache(): void
    {
        Artisan::command('config:cache', function () {
            foreach (Setting::select('id', 'key', 'config', 'format')->get() as $setting) {
                config([$setting->key => $setting->config]);
            }
        });
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

    /**
     * Since these four classess use a different db connection
     * (even though they are on the same server as of 2021), we
     * need to force the same connection instance in tests so
     * they can access data in a transaction.
     *
     * TODO: remove the `data` connection
     */
    protected function useSameDBConnection()
    {
        $fakeManager = new class($this->app, $this->app['db.factory']) extends DatabaseManager {
            public function connection($name = null)
            {
                return parent::connection($this->getDefaultConnection());
            }
        };
        $this->app->instance('db', $fakeManager);

        ProcessRequest::setConnectionResolver($fakeManager);
        ProcessRequestLock::setConnectionResolver($fakeManager);
        RequestUserPermission::setConnectionResolver($fakeManager);
        SecurityLog::setConnectionResolver($fakeManager);
    }
}
