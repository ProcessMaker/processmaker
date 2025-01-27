<?php

namespace Tests;

use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Models\User;
use RuntimeException;

final class GenerateAccessTokenTest extends TestCase
{
    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }

    public function testGetNewToken(): void
    {
        $user = User::factory()->create();
        $tokenRef = new GenerateAccessToken($user);
        // use regex to verify JWT
        $this->assertMatchesRegularExpression("/^[A-Za-z0-9-_=]+\.[A-Za-z0-9-_=]+\.[A-Za-z0-9-_=]+$/", $tokenRef->getToken());
    }

    public function testDeleteToken(): void
    {
        $user = User::factory()->create();
        $this->assertEquals(0, $user->tokens()->count());

        $tokenRef = new GenerateAccessToken($user);
        $this->assertEquals(1, $user->tokens()->count());

        $tokenRef->delete();
        $this->assertEquals(0, $user->tokens()->count());
    }
}
