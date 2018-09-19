<?php
namespace Tests\Feature\Shared;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;

trait ApiCallWithUser 
{
    protected $user;
    private $_debug_response;

    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS)
        ]);
    }

    /**
     * Display debugging information from the response if the test failed
     */
    public function tearDown()
    {
        parent::tearDown();
        if ($this->hasFailed() && isset($this->_debug_response)) {
            $json = $this->_debug_response->json();
            unset($json['trace']);
        }
    }

    protected function apiCall($method, $url, $params = [])
    {
        $response = $this->actingAs($this->user, 'api')
                         ->json($method, $url, $params);
        $this->_debug_response = $response;
        return $response;
    }
}