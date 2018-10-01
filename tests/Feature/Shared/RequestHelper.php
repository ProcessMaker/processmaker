<?php
namespace Tests\Feature\Shared;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;

trait RequestHelper
{
    protected $user;

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
        return $response;
    }

    protected function webCall($method, $url, $params = [])
    {
        $response = $this->actingAs($this->user, 'api')
                         ->call($method, $url, $params);
        return $response;
    }
    protected function webGet($url, $params = [])
    {
        return $this->webCall('GET', $url, $params);
    }
}
