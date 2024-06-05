<?php

namespace Tests\Feature\Shared;

use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;

trait RequestHelper
{
    protected $user;

    protected $debug = true;

    private $_debug_response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_administrator' => true,
        ]);

        if ($this->withPermissions === true) {
            //Run the permission seeder
            (new PermissionSeeder)->run();

            // Reboot our AuthServiceProvider. This is necessary so that it can
            // pick up the new permissions and setup gates for each of them.
            $asp = new AuthServiceProvider(app());
            $asp->boot();
        }

        if (method_exists($this, 'withUserSetup')) {
            $this->withUserSetup();
        }
    }

    protected function apiCall($method, $url, $params = [])
    {
        // If the url was generated using the route() helper,
        // strip out the http://.../api/1.0 part of it;
        $url = preg_replace('/^.*\/api\//i', '', $url);

        $response = $this->actingAs($this->user, 'api')
                         ->json($method, '/api/' . $url, $params);
        $this->_debug_response = $response;

        return $response;
    }

    protected function webCall($method, $url, $params = [])
    {
        $response = $this->actingAs($this->user, 'web')
                         ->call($method, $url, $params);
        $this->_debug_response = $response;

        return $response;
    }

    protected function webGet($url, $params = [])
    {
        return $this->webCall('GET', $url, $params);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (!$this->debug) {
            return;
        }

        if ($this->hasFailed() && isset($this->_debug_response)) {
            try {
                $json = $this->_debug_response->json();
            } catch (\Exception $e) {
                $exception = $this->_debug_response->exception;
                $json = [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTrace(),
                ];
            }
            if (!isset($json['trace'])) {
                return;
            }
            $json['trace'] = array_slice($json['trace'], 0, 15);
            error_log((isset($this->_debug_response->exception) ? get_class($this->_debug_response->exception) : '') . ': ' . $json['message']);
            isset($json['file']) ? error_log($json['file'] . ':' . $json['line'])
                : error_log($json['class'] . '::' . $json['function']);
            foreach ($json['trace'] as $trace) {
                isset($trace['file']) ? error_log($trace['file'] . ':' . $trace['line'])
                : error_log($trace['class'] . '::' . $trace['function']);
            }
        }
    }
}
