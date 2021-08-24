<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use Tests\Feature\Shared\RequestHelper;

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
        $groupId = factory(Group::class)->create()->getKey();
        // get the URL
        $response = $this->webCall('GET', '/admin/groups/' . $groupId . '/edit');

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('admin.groups.edit');
        $response->assertSee('Edit Group');
    }

    /**
     * Test to make sure the manager user is loaded
     *
     * @return void
     */
    public function testEditRouteHasManager()
    {
        $group = factory(Group::class)->create();
        $groupId = $group->getKey();
        // get the URL
        $response = $this->webCall('GET', '/admin/groups/' . $groupId . '/edit');

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('admin.groups.edit');
        $response->assertSee('Group Manager');
        $response->assertViewHas('group', function ($formData) use ($group) {
            return $formData->manager_id === $group->manager_id;
        });
    }
}
