<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
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
    protected $signature = 'processmaker:build-script-executor {lang}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        system("docker build -t processmaker4/executor-${lang}:latest ${dockerDir}");
    }
}
