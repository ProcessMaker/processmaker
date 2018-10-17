<?php
namespace Tests\Feature\Shared;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;

trait RequestHelper
{
    protected $user;
    protected $debug = true;
    private $_debug_response;

    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => 'password'
        ]);
    }

    protected function apiCall($method, $url, $params = [])
    {
        $response = $this->actingAs($this->user, 'api')
                         ->json($method, $url, $params);
        $this->_debug_response = $response;
        return $response;
    }

    protected function webCall($method, $url, $params = [])
    {
        $response = $this->actingAs($this->user, 'api')
                         ->call($method, $url, $params);
        $this->_debug_response = $response;
        return $response;
    }
    protected function webGet($url, $params = [])
    {
        return $this->webCall('GET', $url, $params);
    }

    public function tearDown()
    {
        parent::tearDown();
        if (!$this->debug) { return; }

        if ($this->hasFailed() && isset($this->_debug_response)) {
            $json = $this->_debug_response->json();
            unset($json['trace']);
            echo "\nResponse Debug Information:\n";
            var_dump($json);
            echo "\n";
        }
    }
}
