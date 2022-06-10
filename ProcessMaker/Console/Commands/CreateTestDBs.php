<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CreateTestDBs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:create-test-dbs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clone the test database for parallel test runs';

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
        $processes = env('PARALLEL_TEST_PROCESSES');
        if (!$processes) {
            throw new Exception('PARALLEL_TEST_PROCESSES not set');
        }

        $dbConnectionArgs = '-h ' . env('DB_HOSTNAME') . ' -P ' . env('DB_PORT') . ' -u ' . env('DB_USERNAME') . " -p'" . env('DB_PASSWORD') . "'";
        $file = tempnam(sys_get_temp_dir(), 'dump');
        $cmd = "mysqldump $dbConnectionArgs " . env('DB_DATABASE') . " > $file";
        (new Process($cmd))->mustRun();

        $importCommands = [];
        foreach (range(1, $processes) as $process) {
            $database = "test_$process";
            $this->info("Creating database $database");

            $cmd = "mysql $dbConnectionArgs -e 'DROP DATABASE IF EXISTS $database'";
            (new Process($cmd))->mustRun();

            $cmd = "mysql $dbConnectionArgs -e 'CREATE DATABASE $database'";
            (new Process($cmd))->mustRun();

            $cmd = "mysql $dbConnectionArgs $database < $file";
            // (new Process($cmd))->mustRun();
            $importCommands[] = $cmd;
        }

        $processes = [];
        foreach ($importCommands as $importCommand) {
            $process = new Process($importCommand);
            $process->start();
            $processes[] = $process;
        }

        $timeout = 10;
        $start = time();
        while (count($processes) > 0) {
            if ((time() - $start) > $timeout) {
                throw new \Exception("Timeout");
            }
            foreach($processes as $i => $process) {
                if (!$process->isRunning()) {
                    unset($processes[$i]);
                }
            }
            usleep(100);
        }

    }
}
