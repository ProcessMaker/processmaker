<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\Process;

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
        factory(Process::class)->create(['name'=>'TestProcess']);
        $response = $this->webGet('/processes');
        $response->assertStatus(200);
        $response->assertViewIs('processes.index');
        $response->assertSee('TestProcess');
    }

    // public function testEdit()
    // {
    //   $process_uuid = factory(User::class)->create()->uuid_text;
    //   $response = $this->webGet('processes/'.$process_uuid . '/edit');
    // //   $response->assertStatus(200);
    //   $response->assertViewIs('processes.edit');
    // }

    // public function testCreateRoute()
    // {

    //   // get the URL
    //   $response = $this->apiCall('GET', '/admin/users/create');

    //   $response->assertStatus(200);
    //   // check the correct view is called
    //   $response->assertViewIs('admin.users.create');

    // }
}
