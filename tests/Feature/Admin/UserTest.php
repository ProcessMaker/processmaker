<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RequestHelper;

    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testIndexRoute()
    {

        // get the URL
        $response = $this->webCall('GET', '/admin/users');
        // check the correct view is called
        $response->assertViewIs('admin.users.index');

        $response->assertStatus(200);
        $response->assertSee('Users');
    }

    /**
     * Test to make sure the controller and route work wiht the view
     *
     * @return void
     */
    public function testEditRoute()
    {
        $user_id = factory(User::class)->create()->id;
        // get the URL
        $response = $this->webCall('GET', '/admin/users/' . $user_id . '/edit');

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('admin.users.edit');
        $response->assertSee('Edit User');
    }

    /**
     * Test to make sure the additional information shows in edit user
     * @return void
     */
    public function testCanSeeAditionalInformationInEditRoute()
    {
        $user_id = factory(User::class)->create()->id;
        factory(Setting::class)->create([
            'key' => 'users.properties',
            'config' => '{"MyVar":"Test Var"}',
            'format' => 'object',
            'group' => 'Users',
        ]);
        // get the URL
        $response = $this->webCall('GET', '/admin/users/' . $user_id . '/edit');
        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('admin.users.edit');
        $response->assertSee('Additional Information');
    }

    /**
     * Test to make sure the additional information not shows in profile
     * @return void
     */
    public function testCannotSeeAditionalInformationInProfileRoute()
    {
        factory(User::class)->create()->id;
        factory(Setting::class)->create([
            'key' => 'users.properties',
            'config' => '{"MyVar":"Test Var"}',
            'format' => 'object',
            'group' => 'Users',
        ]);
        // get the URL
        $response = $this->webCall('GET', '/profile/edit');
        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('profile.edit');
        $response->assertDontSee('Additional Information');
    }
}
