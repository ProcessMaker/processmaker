<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Test edit data
 */
class DuplicatedRoutesTest extends TestCase
{
    /**
     * Verify the magic variables for a valid request token
     */
    public function testRoutesCacheGeneration()
    {
        $this->artisan('route:cache');

        $this->assertTrue(true);
    }
}
