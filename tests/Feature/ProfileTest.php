<?php

namespace Tests\Feature;

use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RequestHelper;

    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testEditRoute()
    {
        $user_id = User::factory()->create()->id;
        // get the URL
        $response = $this->webCall('GET', '/profile/edit');

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('profile.edit');
    }

    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testShowRoute()
    {
        $user_id = User::factory()->create()->id;
        // get the URL
        $response = $this->webCall('GET', '/profile/' . $user_id);

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('profile.show');
    }
}
