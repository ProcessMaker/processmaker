<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\User;

class ProcessTest extends TestCase
{
    use DatabaseTransactions;
    use RequestHelper;

    // protected function setUp()
    // {
    //     parent::setUp();
    //     $this->user = factory(User::class)->create();
    // }

    protected $structure = [
        'uuid',
        'updated_at',
        'created_at',
    ];

    public function testIndex() {
        $response = $this->webGet('/processes');
        $response->assertStatus(200);
        $response->assertViewIs('processes.index');
        $response->assertSee('id="processIndex"');
    }
}
