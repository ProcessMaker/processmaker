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

    public function testDBFacadeQuery()
    {
        $record = DB::table('USERS')->where([
            'USR_UID' => '00000000000000000000000000000001'
        ])->first();
        $this->assertEquals('admin', $record->USR_USERNAME);
    }

    public function testDBFacadeQueryWithExternalMySQLDatabase()
    {
        // Our test external database is created in our tests/bootstrap.php file
        // We'll use our factories to create our process and database
        $process = factory(Process::class)->create();
        // Let's create an external DB to ourselves
        $externalDB = factory(DbSource::class)->create([
            'DBS_SERVER' => env('DB_HOST'),
            'DBS_PORT' => env('DB_PORT'),
            'DBS_USERNAME' => env('DB_USERNAME'),
            // Remember, we have to do some encryption here @see DbSourceFactory.php
            'DBS_PASSWORD' => Crypt::encryptString( env('DB_PASSWORD', 'testexternal') ),
            'DBS_DATABASE_NAME' => 'testexternal',
            'PRO_UID' => $process->PRO_UID
        ]);
        DatabaseManager::registerDatabaseConnectionsForProcess($process);
        $results = DB::connection($externalDB->DBS_UID)->table('test')->get();
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
            'DBS_SERVER' => env('MSSQL_HOST'),
            'DBS_PORT' => env('MSSQL_PORT'),
            'DBS_TYPE' => 'sqlsrv',
            'DBS_USERNAME' => env('MSSQL_USERNAME', 'sa'),
            // Remember, we have to do some encryption here @see DbSourceFactory.php
            'DBS_PASSWORD' => Crypt::encryptString( env('MSSQL_PASSWORD', 'testexternal') ),
            'DBS_DATABASE_NAME' => env('MSSQL_DATABASE', 'testexternal'),
            'PRO_UID' => $process->PRO_UID
        ]);
        DatabaseManager::registerDatabaseConnectionsForProcess($process);
        $results = DB::connection($externalDB->DBS_UID)->table('test')->get();
        $this->assertCount(1, $results);
    }
}