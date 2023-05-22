<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RequestHelper;

    /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testIndexRoute()
    {
        $response = $this->webCall('GET', '/admin/groups');
        $response->assertStatus(200);
        $response->assertViewIs('admin.groups.index');
    }

    /**
     * Test to make sure the controller and route work with the view EDIT
     *
     * @return void
     */
    public function testEditRoute()
    {
        $groupId = Group::factory()->create()->getKey();
        // get the URL
        $response = $this->webCall('GET', '/admin/groups/' . $groupId . '/edit');

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('admin.groups.edit');
        $response->assertSee('Edit Group');
    }
}
