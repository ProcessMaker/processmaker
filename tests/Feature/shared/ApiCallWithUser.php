<?php
namespace Tests\Feature\Shared;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;

trait ApiCallWithUser 
{
    protected $user;

    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS)
        ]);
    }

    protected function apiCall($method, $url, $params = [])
    {
        return $this->actingAs($this->user, 'api')
                    ->json($method, $url, $params);
    }
}