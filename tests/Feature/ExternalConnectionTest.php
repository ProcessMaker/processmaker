<?php

namespace Tests\Feature;

use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Connection;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Comment;

/**
 * Test edit data
 */
class ExternalConnectionTest extends TestCase
{
    use RequestHelper;

    /**
     * Test external connection
     */
    public function testVerifyExternalConnection()
    {
        if (!config('database.enable_external_connection')) {
            $this->markTestSkipped('ENABLE_EXTERNAL_CONNECTION is not enabled');
        }
        $connection = DB::connection('data');
        $this->assertInstanceOf(Connection::class, $connection);
        $processRequest = new ProcessRequest();
        $this->assertEquals($connection, $processRequest->getConnection());
        $comment = new Comment();
        $this->assertEquals($connection, $comment->getConnection());
    }
}
