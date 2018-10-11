<?php

namespace Tests\Feature;

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
      factory(Group::class)->create(['name'=>'Test Group']);
      factory(Group::class)->create(['name'=>'Another group']);
      $response = $this->webCall('GET', '/admin/groups');
      $response->assertStatus(200);
      $response->assertViewIs('admin.groups.index');
      $response->assertSee('Test Group');
      $response->assertSee('Another group');
    }
     /**
     * Test to make sure the controller and route work with the view
     *
     * @return void
     */
    public function testEditRoute()
    {
      $group = factory(Group::class)->create(['name'=>"Test Edit"]);
      $response = $this->webCall('GET', '/admin/groups/'.$group->uuid_text . '/edit');
      $response->assertStatus(200);
      $response->assertViewIs('admin.groups.edit');
      $response->assertSee('Test Edit');
    }

    public function testShowRoute() 
    {
        $group = factory(Group::class)->create(['name'=>'Test show']);
        $response = $this->webGet('/admin/groups/'. $group->uuid_text );
        $response->assertStatus(200);
        $response->assertViewIs('admin.groups.show');
        $response->assertSee('Test show');
    }
}
