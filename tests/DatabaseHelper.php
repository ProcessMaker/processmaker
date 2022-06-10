<?php

namespace Tests;

use Exception;
use Symfony\Component\Process\Process;
use DB;
use Artisan;

class DatabaseHelper {

    private $dumpFile;

    public function __construct($dumpFile = null)
    {
        if (!$dumpFile) {
            $dumpFile = sys_get_temp_dir() . '/mysqldump.sql';
        }
        $this->dumpFile = $dumpFile;
    }

    private function dbConnectionArgs($connection)
    {
        $config = $connection->getConfig();
        $passwordPart = empty($config['password']) ? '' : " -p'" . $config['password'] . "'";
        return '-h ' . $config['host'] . ' -P ' . $config['port'] . ' -u ' . $config['username'] . $passwordPart;
    }

    public function createTestDBs()
    {
        $connection = DB::connection();
        $dbConnectionArgs = $this->dbConnectionArgs($connection);
        $cmd = "mysqldump $dbConnectionArgs " . env('DB_DATABASE') . " > " . $this->dumpFile;
        (new Process($cmd))->mustRun();
        
        $processes = env('PARALLEL_TEST_PROCESSES');
        if (!$processes) {
            return;
        }

        $importCommands = [];
        foreach (range(1, $processes) as $process) {
            $database = "test_$process";
            testLog("Creating database $database");

            $cmd = "mysql $dbConnectionArgs -e 'DROP DATABASE IF EXISTS $database'";
            (new Process($cmd))->mustRun();

            $cmd = "mysql $dbConnectionArgs -e 'CREATE DATABASE $database'";
            (new Process($cmd))->mustRun();

            $cmd = "mysql $dbConnectionArgs $database < " . $this->dumpFile;
            // (new Process($cmd))->mustRun();
            $importCommands[] = $cmd;
        }

        $processes = [];
        foreach ($importCommands as $importCommand) {
            $process = new Process($importCommand);
            $process->start();
            $processes[] = $process;
        }

        $timeout = 20;
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

    public function replaceCurrentDatabase()
    {
            $connection1 = DB::connection('processmaker');
            $connection2 = DB::connection('data');
            $database = $connection1->getDatabaseName();
            $dbConnectionArgs = $this->dbConnectionArgs($connection1);
            $connection1->disconnect();
            $connection2->disconnect();

            $cmd = "mysql $dbConnectionArgs -e 'DROP DATABASE $database; CREATE DATABASE $database'";
            (new Process($cmd))->run();
            // Artisan::call("db:wipe", ['database' => Db::connection()->getName()]);
            // testLog("db:wipe " . Artisan::output());

            $cmd = "mysql $dbConnectionArgs $database < " . $this->dumpFile;
            (new Process($cmd))->mustRun();

            testLog("restored database $database");
    }
}
