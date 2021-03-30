<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
        $this->test("BroadcastService", [$this, 'testBroadcastService']);
        $this->test("EchoService", [$this, 'testEchoService']);
        $this->test("EmailService", [$this, 'testEmailService']);
        $this->test("DockerServie", [$this, 'testDockerServie']);
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
        // Check connection
        $connection = DB::connection();
        $connection->table('migrations')->first();
        // Check migration status
        $this->call('migrate:status');
    }

    private function testBroadcastService()
    {
    }

    private function testEchoService()
    {
    }

    private function testEmailService()
    {
    }

    private function testDockerServie()
    {
    }
}
