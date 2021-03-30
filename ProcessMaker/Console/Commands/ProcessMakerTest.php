<?php

namespace ProcessMaker\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Jobs\TestStatusJob;
use Throwable;

class ProcessMakerTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tests all of the key features / configuration of the application';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->test("DBConnection", [$this, 'testDBConnection']);
        $this->test("Horizon", [$this, 'testHorizonService']);
        $this->test("Broadcast", [$this, 'testBroadcastService']);
        $this->test("Echo", [$this, 'testEchoService']);
        $this->test("Docker", [$this, 'testDockerService']);
        $this->test("Email", [$this, 'testEmailService']);
    }

    private function test($name, callable $callback)
    {
        try {
            $callback();
        } catch (Throwable $error) {
            $this->error("[{$name}] " . $error->getMessage());
            return false;
        }
        $this->info("[{$name}] OK");
    }

    private function testDBConnection()
    {
        // Check main connection
        $connection = DB::connection();
        $connection->table('migrations')->first();
        // Check migration status
        $this->checkMigrationStatus();
        // Check data connection
        $dataConnection = DB::connection('data');
        $dataConnection->table('process_requests')->first();
        // Clear test status table
        $connection->table('test_status')->truncate();
    }

    private function checkMigrationStatus()
    {
        exec('php artisan migrate:status', $out, $r);
        if ($r !== 0) {
            throw new Exception('Unable to check migrate:status');
        }
        $missingMigrations = 0;
        foreach ($out as $line) {
            if (strpos($line, '| No')!==false) {
                $missingMigrations++;
            }
        }
        if ($missingMigrations > 0) {
            throw new Exception("Missing {$missingMigrations} migrations");
        }
    }

    private function testHorizonService()
    {
        $testDispatchNow = 'Test immediate Jobs';
        TestStatusJob::dispatchNow($testDispatchNow);
        $this->waitTestPassed($testDispatchNow, 5);

        $testDelayedWorkers = 'Test dispatch Jobs';
        TestStatusJob::dispatch($testDelayedWorkers);
        $this->waitTestPassed($testDelayedWorkers, 5);

        $testDelayedWorkers = 'Test dispatch delayed Jobs';
        TestStatusJob::dispatch($testDelayedWorkers)->delay(Carbon::now()->addSeconds(1));
        $this->waitTestPassed($testDelayedWorkers, 10);
    }

    private function testBroadcastService()
    {
        // Show link to complete broadcast tests
        $this->info('To continue, please open this url: '. url('/test_status'));
        $this->waitTestPassed('Message acknowledgement');
    }

    private function waitTestPassed($name, $timeout = 60)
    {
        for ($i = 0; $i<$timeout; $i++) {
            $count = DB::table('test_status')->where('name', $name)->count();
            if ($count > 0) {
                return true;
            }
            sleep(1);
        }
        throw new Exception('FAILED: ' . $name);
    }

    private function testEchoService()
    {
        $this->waitTestPassed('EchoService');
    }

    private function testDockerService()
    {
    }

    private function testEmailService()
    {
    }
}
