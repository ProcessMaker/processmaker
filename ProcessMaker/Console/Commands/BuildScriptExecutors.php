<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Events\BuildScriptExecutor;
use ProcessMaker\Exception\InvalidDockerImageException;
use ProcessMaker\Facades\Docker;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\ScriptRunners\Base;
use UnexpectedValueException;

class BuildScriptExecutors extends Command
{
    /**
     * The name and signature of the console command.
     *
     *
     * @var string
     */
    protected $signature = 'processmaker:build-script-executor {lang} {user?} {--rebuild}';

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
     * The path to save the current running process id
     *
     * @var string
     */
    protected $pidFilePath = null;

    /**
     * The path to the executor package
     *
     * @var string
     */
    protected $packagePath = null;

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
        if (env('PM_CI', false)) {
            // Do not run in CI environment
            return;
        }

        $this->userId = $this->argument('user');
        try {
            $this->buildExecutor();
        } catch (Exception $e) {
            if ($this->userId) {
                event(new BuildScriptExecutor($e->getMessage(), $this->userId, 'error'));
            }
            throw $e;
        } finally {
            if ($this->packagePath && file_exists($this->packagePath . '/Dockerfile.custom')) {
                unlink($this->packagePath . '/Dockerfile.custom');
            }
            if ($this->pidFilePath) {
                unlink($this->pidFilePath);
            }
        }
    }

    public function buildExecutor()
    {
        $this->savePid();
        $this->sendEvent($this->pidFilePath, 'starting');

        $langArg = $this->argument('lang');
        if (is_numeric($langArg)) {
            $scriptExecutor = ScriptExecutor::findOrFail($langArg);
        } else {
            $scriptExecutor = ScriptExecutor::initialExecutor($langArg);
        }
        $lang = $scriptExecutor->language;

        if (!$this->option('rebuild')) {
            $this->info('Attempting to use an existing docker image');
            if ($scriptExecutor->dockerImageExists()) {
                $this->info('Already associated with a docker image');

                return;
            }

            $success = $this->associateWithExistingImage($scriptExecutor);
            if ($success) {
                $this->info('Docker Image Associated');

                // we associated with an existing image, no need to build
                return;
            } else {
                $this->info('Could not associate with an existing docker image. Building image now.');
            }
        }

        $this->packagePath = $packagePath = ScriptExecutor::packagePath($lang);

        $sdkLanguage = $scriptExecutor->language;
        $config = ScriptExecutor::config($scriptExecutor->language);
        if (isset($config['sdk'])) {
            $sdkLanguage = $config['sdk'];
        }
        if ($sdkLanguage) {
            $this->info("Building for language: $sdkLanguage");
            $this->info('Generating SDK json document');
            $this->artisan('l5-swagger:generate');

            $sdkDir = $packagePath . '/sdk';

            if (!is_dir($sdkDir)) {
                mkdir($sdkDir, 0755, true);
            }

            $this->info('Building the SDK');
            $cmd = "processmaker:sdk $sdkLanguage $sdkDir --clean";
            if ($this->userId) {
                $cmd .= ' --user-id=' . $this->userId;
            }
            $this->artisan($cmd);
            $this->info("SDK is at {$sdkDir}");
        }

        $dockerfile = ScriptExecutor::initDockerfile($lang) . "\n" . $scriptExecutor->config;

        $this->info("Dockerfile:\n  " . implode("\n  ", explode("\n", $dockerfile)));
        file_put_contents($packagePath . '/Dockerfile.custom', $dockerfile);

        $this->info('Building the docker executor');

        $image = $scriptExecutor->dockerImageName();
        $command = Docker::command() .
            " build --build-arg SDK_DIR=./sdk -t {$image} -f {$packagePath}/Dockerfile.custom {$packagePath}";

        $this->execCommand($command);

        $isNayra = $scriptExecutor->language === Base::NAYRA_LANG;
        if ($isNayra) {
            $instanceName = config('app.instance');
            $this->info('Stop existing nayra container');
            $this->execCommand(Docker::command() . " stop {$instanceName}_nayra 2>&1 || true");
            $this->execCommand(Docker::command() . " rm {$instanceName}_nayra 2>&1 || true");
            $this->info('Bring up the nayra container');
            $this->execCommand(Docker::command() . ' run -d --name ' . $instanceName . '_nayra ' . $image);
            $this->info('Get IP address of the nayra container');
            $ip = '';
            for ($i = 0; $i < 10; $i++) {
                $ip = exec(
                    Docker::command()
                    . " inspect --format '{{ .NetworkSettings.IPAddress }}' {$instanceName}_nayra"
                );
                if ($ip) {
                    $this->info('Nayra container IP: ' . $ip);
                    Base::setNayraAddresses([$ip]);
                    $this->sendEvent(0, 'done');
                    break;
                }
                sleep(1);
            }
            if (!$ip) {
                throw new UnexpectedValueException('Could not get IP address of the nayra container');
            }
        }
    }

    private function execCommand(string $command)
    {
        if ($this->userId) {
            $this->runProc(
                $command,
                function () {
                    // Command starting
                },
                function ($output) {
                    // Command output callback
                    $this->sendEvent($output, 'running');
                },
                function ($exitCode) {
                    // Command finished callback
                    $this->sendEvent($exitCode, 'done');
                }
            );
        } else {
            system($command);
        }
    }

    public function info($text, $verbosity = null)
    {
        if ($this->userId) {
            $this->sendEvent($text . "\n", 'running');
        }
        parent::info($text, $verbosity);
    }

    private function sendEvent($output, $status)
    {
        if ($this->userId) {
            event(new BuildScriptExecutor($output, $this->userId, $status));
        } else {
            $this->info("$status - $output");
        }
    }

    private function artisan($cmd)
    {
        \Artisan::call($cmd);
    }

    private function savePid()
    {
        $pid = getmypid();
        $this->pidFilePath = tempnam('/tmp', 'build_script_executor_');
        file_put_contents($this->pidFilePath, $pid);
    }

    private function runProc($cmd, $start, $callback, $done)
    {
        $dsc = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $process = proc_open("($cmd) 2>&1", $dsc, $pipes);

        $start();

        while (!feof($pipes[1])) {
            $callback(fgets($pipes[1]));
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $done($exitCode);
    }

    private function associateWithExistingImage($executor)
    {
        $images = ScriptExecutor::listOfExecutorImages($executor->language);
        $instance = config('app.instance');
        foreach ($images as $image) {
            if (!preg_match('/executor-' . $instance . '-.+-(\d+):/', $image, $match)) {
                throw new InvalidDockerImageException('Not a valid image:' . (string) $image);
            }
            $id = intval($match[1]);
            $existingExecutor = ScriptExecutor::find($id);
            if ($existingExecutor && $existingExecutor->id !== $id) {
                // Already associated with another script executor
                continue;
            }
            // Rename unassociated image with this executor's id
            $this->renameDockerImage($image, $executor->dockerImageName());

            return true;
        }

        return false;
    }

    private function renameDockerImage($old, $new)
    {
        system(Docker::command() . " tag $old $new");
        system(Docker::command() . " rmi $old");
    }
}
