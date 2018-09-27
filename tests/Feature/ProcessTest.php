<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;

class ProcessTest extends TestCase
{
    use DatabaseTransactions;
    use RequestHelper;

    // protected function setUp()
    // {
    //     parent::setUp();
    //     $this->user = factory(User::class)->create();
    // }

    public function testIndex() {  
        factory(Process::class)->create(['name'=>'Test Process']);
        factory(Process::class)->create(['name'=>'Another Process']);
        // factory(ProcessCategory::class)->create(['name'=>'Test Category']);
        $response = $this->webGet('/processes');
        $response->assertStatus(200);
        $response->assertViewIs('processes.index');
        $response->assertSee('Test Process');
        $response->assertSee('Another Process');
    }

    public function testEdit()
    {
      $process = factory(Process::class)->create(['name'=>'Test Edit']);
      $response = $this->webGet('processes/'.$process->uuid_text . '/edit');
      $response->assertStatus(200);
      $response->assertViewIs('processes.edit');
      $response->assertSee('Test Edit');
    }

    // public function testCreateRoute()
    // {

    //   // get the URL
    //   $response = $this->apiCall('GET', '/admin/users/create');

    //   $response->assertStatus(200);
    //   // check the correct view is called
    //   $response->assertViewIs('admin.users.create');

    // }
}
