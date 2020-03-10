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
        $this->userId = $this->argument('user');
        
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
        $this->info("SDK is at ${sdkDir}");

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

        $tag = 'v1.0.0'; // Hard coding this for now until we get versions set up
        $command = "docker build --build-arg SDK_DIR=/sdk -t processmaker4/executor-instance-${lang}:${tag} ${dockerDir}";

        if ($this->userId) {
            $this->runProc(
                $command,
                function($pidFilePath) {
                    // Command starting
                    $this->sendEvent($pidFilePath, 'starting');
                },
                function($output) {
                    // Command output callback
                    $this->sendEvent($output, 'running');
                },
                function($exitCode) {
                    // Command finished callback
                    $this->sendEvent($exitCode, 'done');
                }
            );
        } else {
            system($command);
        }
    }

    public function info($text, $verbosity = null) {
        if ($this->userId) {
            $this->sendEvent($text . "\n", 'running');
        }
        parent::info($text, $verbosity);
    }

    private function sendEvent($output, $status)
    {
        event(new BuildScriptExecutor($output, $this->userId, $status));
    }

    private function savePid($process)
    {
        $pid = proc_get_status($process)['pid'];
        $pidFilePath = tempnam('/tmp', 'build_script_executor_');
        file_put_contents($pidFilePath, $pid);
        return $pidFilePath;
    }

    private function runProc($cmd, $start, $callback, $done)
    {
        $dsc = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $process = proc_open("($cmd) 2>&1", $dsc, $pipes);

        $pidFilePath = $this->savePid($process);
        $start($pidFilePath);

        while(!feof($pipes[1])) {
            $callback(fgets($pipes[1]));
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        unlink($pidFilePath);
        $exitCode = proc_close($process);
        $done($exitCode);
    }
}
