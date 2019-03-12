<?php
namespace Tests;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Models\User;
use RuntimeException;

class GenerateAccessTokenTest extends TestCase
{
    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }

    public function testGetNewToken()
    {
        $user = factory(User::class)->create();
        $tokenRef = new GenerateAccessToken($user);
        // use regex to verify JWT
        $this->assertRegExp("/^[A-Za-z0-9-_=]+\.[A-Za-z0-9-_=]+\.[A-Za-z0-9-_=]+$/", $tokenRef->getToken());
    }

    public function testDeleteToken()
    {
        $user = factory(User::class)->create();
        $this->assertEquals(0, $user->tokens()->count());

        $tokenRef = new GenerateAccessToken($user);
        $this->assertEquals(1, $user->tokens()->count());

        $tokenRef->delete();
        $this->assertEquals(0, $user->tokens()->count());
    }
}
