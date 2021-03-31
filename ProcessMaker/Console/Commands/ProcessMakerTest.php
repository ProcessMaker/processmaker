<?php

namespace ProcessMaker\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use ProcessMaker\Jobs\TestStatusJob;
use ProcessMaker\Mail\TestStatusEmail;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
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
        $this->info('To continue, please open the following url:');
        $this->warn(url('/test_status'));
        $this->waitTestPassed('Message acknowledgement');
    }

    private function waitTestPassed($name, $timeout = 120)
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

    private function testDockerService()
    {
        $this->testPHPDocker();
        $this->testJavascriptDocker();
        $this->testLuaDocker();
    }

    private function testScriptDocker($language, $code)
    {
        $script = new Script();
        $script->language = $language;
        $script->run_as_user_id = 1;
        $executor = ScriptExecutor::where('language', $language)->firstOrFail();
        $script->script_executor_id = $executor->id;
        $script->code = $code;
        $res = $script->runScript(["foo" => "bar"], ["conf"=>"val"]);
        if (!is_array($res) || empty($res['output'])) {
            throw new Exception("Failed execution of `{$language}` script.");
        }
        if (json_encode($res['output']['data1']) !== '{"foo":"bar"}' ||
            json_encode($res['output']['config1']) !== '{"conf":"val"}'
        ) {
            throw new Exception("Unexpected response of the {$language} script execution.\n" . json_encode($res['output']));
        }
    }

    private function testPHPDocker()
    {
        $this->testScriptDocker('php', '<?php return ["data1" => $data, "config1" => $config];');
    }

    private function testJavascriptDocker()
    {
        $this->testScriptDocker('javascript', 'return {"data1": data, "config1": config};');
    }

    private function testLuaDocker()
    {
        $this->testScriptDocker('lua', 'res = {}; res.data1 = data; res.config1 = config; return res;');
    }

    private function testEmailService()
    {
        $email = $this->ask('Send email to');
        Mail::to($email)->send(new TestStatusEmail());
        $this->info('An email was sent to ' . $email . '. Please open it to complete the email test.');
        $this->waitTestPassed('Email received');
    }
}
