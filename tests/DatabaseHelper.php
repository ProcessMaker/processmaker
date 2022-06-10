<?php

namespace Tests;

use Exception;
use Symfony\Component\Process\Process;
use DB;

class DatabaseHelper {

    private $dumpFile;

    public function __construct($dumpFile = null)
    {
        if (!$dumpFile) {
            $dumpFile = sys_get_temp_dir() . '/mysqldump.sql';
        }
        $this->dumpFile = $dumpFile;
    }

    private function dbConnectionArgs()
    {
        return '-h ' . env('DB_HOSTNAME') . ' -P ' . env('DB_PORT') . ' -u ' . env('DB_USERNAME') . " -p'" . env('DB_PASSWORD') . "'";
    }

    public function createTestDBs()
    {
        $processes = env('PARALLEL_TEST_PROCESSES');
        if (!$processes) {
            throw new Exception('PARALLEL_TEST_PROCESSES not set');
        }
        $dbConnectionArgs = $this->dbConnectionArgs();
        $cmd = "mysqldump $dbConnectionArgs " . env('DB_DATABASE') . " > " . $this->dumpFile;
        (new Process($cmd))->mustRun();

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
            $database = DB::connection()->getDatabaseName();
            $dbConnectionArgs = $this->dbConnectionArgs();

            $cmd = "mysql $dbConnectionArgs -e 'DROP DATABASE $database; CREATE DATABASE $database'";
            (new Process($cmd))->mustRun();

            $cmd = "mysql $dbConnectionArgs $database < " . $this->dumpFile;
            (new Process($cmd))->mustRun();

            testLog("restored database $database");
    }
}
