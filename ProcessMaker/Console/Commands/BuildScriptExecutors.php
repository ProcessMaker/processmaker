<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Events\BuildScriptExecutor;
use ProcessMaker\BuildSdk;
use \Exception;

class BuildScriptExecutors extends Command
{
    /**
     * The name and signature of the console command.
     *
     *
     * @var string
     */
    protected $signature = 'processmaker:build-script-executor {lang} {user?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';
    
    /**
     * The user ID to send the broadcast event to.
     *
     * @var int
     */
    protected $userId = null;

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
        $lang = $this->argument('lang');
        $this->info("Building for language: $lang");

        $this->info("Generating SDK json document");
        \Artisan::call('l5-swagger:generate');

        $dockerDir = sys_get_temp_dir() . "/pm4-docker-builds/${lang}";
        $sdkDir = $dockerDir . "/sdk";

        if (!is_dir($sdkDir)) {
            mkdir($sdkDir, 0755, true);
        }

        $this->info("Building the SDK");
        \Artisan::call("processmaker:sdk", [
            'language' => $lang,
            'output' => $sdkDir
        ]);

        $dockerfile = '';
        $initDockerfile = config('script-runners.' . $lang . '.init_dockerfile');
        if ($initDockerfile) {
            $dockerfile .= $initDockerfile;
        }
        $dockerfile .= "\n";
        $appDockerfilePath = storage_path("docker-build-config/Dockerfile-${lang}");
        if (file_exists($appDockerfilePath)) {
            $dockerfile .= file_get_contents($appDockerfilePath);
        }

        $this->info("Dockerfile:\n  " . implode("\n  ", explode("\n", $dockerfile)));
        file_put_contents($dockerDir . '/Dockerfile', $dockerfile);

        $this->info("Building the docker executor");

        $command = "docker build -t processmaker4/executor-${lang}:latest ${dockerDir}";

        $this->userId = $this->argument('user');
        if ($this->userId) {
            $this->runProc(
                $command,
                function($output) {
                    // Command output callback
                    $this->sendEvent($output, 'running');
                },
                function() {
                    // Command finished callback
                    $this->sendEvent('', 'done');
                }
            );
        } else {
            system($command);
        }
    }

    private function sendEvent($output, $status) {
        event(new BuildScriptExecutor($output, $this->userId, $status));
    }

    private function runProc($cmd, $callback, $done)
    {
        $dsc = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $process = proc_open("($cmd) 2>&1", $dsc, $pipes);

        while(!feof($pipes[1])) {
            $callback(fgets($pipes[1]));
        }

        $done();

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($process);
    }
}
