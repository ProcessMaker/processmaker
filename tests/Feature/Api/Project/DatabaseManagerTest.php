<?php
namespace Tests\Feature\Api\Cases;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Managers\DatabaseManager;
use Tests\TestCase;

class DatabaseManagerTest extends TestCase
{
    use DatabaseTransactions;

    public function testConnection()
    {
        $connectionParams = [];
        $connectionParams['driver'] = env('DB_ADAPTER');
        $connectionParams['host'] = env('DB_HOST');
        $connectionParams['database'] = env('DB_DATABASE');
        $connectionParams['username'] = env('DB_USERNAME');
        $connectionParams['password'] = env('DB_PASSWORD');
        $connectionParams['port'] = env('DB_PORT');

        $dbManager = new DatabaseManager();
        $result = $dbManager->testConnection($connectionParams);
        $this->assertTrue($result, 'A connection with correct parameters should return true');
    }
}