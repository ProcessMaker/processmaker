<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use Tests\Feature\Shared\RequestHelper;

class GroupTest extends TestCase
{
    use RequestHelper;
    use DatabaseTransactions;
     /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testIndexRoute()
    {
      factory(Group::class)->create(['name'=>'Test Group']);
      factory(Group::class)->create(['name'=>'Another group']);
      $response = $this->webCall('GET', '/admin/groups');
      $response->assertViewIs('admin.groups.index');
      $response->assertSee('Test Group');
      $response->assertSee('Another group');
      $response->assertStatus(200);
    }
     /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testEditRoute()
    {

      $group_uuid = factory(Group::class)->create()->uuid_text;
      $response = $this->apiCall('GET', '/admin/groups/'.$group_uuid . '/edit');
      $response->assertStatus(200);
      $response->assertViewIs('admin.groups.edit');

    }
}
