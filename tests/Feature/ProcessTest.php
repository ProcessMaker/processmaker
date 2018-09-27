<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class TasksTest extends TestCase
{
    use DatabaseTransactions;
    use RequestHelper;

    const PROCESS_URL = '/processes';
    const DEFAULT_PASS = 'password';

    protected $structure = [
        'uuid',
        'updated_at',
        'created_at',
    ];

    public function testIndex() {
        $response = $this->webGet(self::PROCESS_URL, []);
        $response->assertViewIs('processes.index');
        $response->assertSee('id="processIndex"');
    }
}
