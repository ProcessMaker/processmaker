<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;

class PreferencesTest extends TestCase
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
      $response = $this->webCall('GET', '/admin/preferences');
      // check the correct view is called
      $response->assertViewIs('admin.preferences.index');

      $response->assertStatus(200);

    }
}
