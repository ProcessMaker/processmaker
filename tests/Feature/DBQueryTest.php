<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Queue\Console\WorkCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use ProcessMaker\Model\DbSource;
use ProcessMaker\Model\Process;
use ProcessMaker\Facades\DatabaseManager;
use Tests\TestCase;
use Propel;
use DbConnections;

class DBQueryTest extends TestCase
{
    use DatabaseTransactions;

    public function testDBFacadeQueryWithExternalMySQLDatabase()
    {
        // Our test external database is created in our tests/bootstrap.php file
        // We'll use our factories to create our process and database
        $process = factory(Process::class)->create();
        // Let's create an external DB to ourselves
        $externalDB = factory(DbSource::class)->create([
            'server' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'username' => env('DB_USERNAME'),
            // Remember, we have to do some encryption here @see DbSourceFactory.php
            'password' => Crypt::encryptString( env('DB_PASSWORD', 'testexternal') ),
            'database_name' => 'testexternal',
            'process_id' => $process->id
        ]);
        DatabaseManager::registerDatabaseConnectionsForProcess($process);
        $results = DB::connection($externalDB->id)->table('test')->get();
        $this->assertCount(1, $results);
    }

    public function testStandardExecuteQueryWithExternalMSSqlDatabase()
    {
        if(!env('RUN_MSSQL_TESTS')) {
            $this->markTestSkipped('MSSQL Related Test Skipped');
        }
        // Our test external database is created in our tests/bootstrap.php file
        // We'll use our factories to create our process and database
        $process = factory(Process::class)->create();
        // Let's create an external DB to ourselves
        $externalDB = factory(DbSource::class)->create([
            'server' => env('MSSQL_HOST'),
            'port' => env('MSSQL_PORT'),
            'type' => 'sqlsrv',
            'username' => env('MSSQL_USERNAME', 'sa'),
            // Remember, we have to do some encryption here @see DbSourceFactory.php
            'password' => Crypt::encryptString( env('MSSQL_PASSWORD', 'testexternal') ),
            'database_name' => env('MSSQL_DATABASE', 'testexternal'),
            'process_id' => $process->id
        ]);
        DatabaseManager::registerDatabaseConnectionsForProcess($process);
        $results = DB::connection($externalDB->id)->table('test')->get();
        $this->assertCount(1, $results);
    }
}