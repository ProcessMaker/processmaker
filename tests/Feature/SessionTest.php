<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionTest extends TestCase
{
    public function test()
    {
        $response = $this->get('/');

        // check 'expire_on_close' => true,
        $this->assertEquals(0, $response->headers->getCookies()[0]->getExpiresTime());

        // check 'path' => '/;samesite=Lax',
        $this->assertEquals('/;samesite=Lax', $response->headers->getCookies()[0]->getPath());

        // Check 'secure' => env('SESSION_SECURE_COOKIE', true),
        $this->assertTrue($response->headers->getCookies()[0]->isSecure());
    }
}
