<?php

namespace ProcessMaker\Models;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Console\Commands\BuildScriptExecutors;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Facades\Docker;
use ProcessMaker\ScriptRunners\Base;
use RuntimeException;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use UnexpectedValueException;

/**
 * Execute a docker container copying files to interchange information.
 */
trait ScriptDockerNayraTrait
{

    private $schema = 'http';
    public static $nayraPort = 8080;

    /**
     * Execute the script task using Nayra Docker.
     *
     * @return string|bool
     */
    public function handleNayraDocker(string $code, array $data, array $config, $timeout, array $environmentVariables)
    {
        $envVariables = [];
        foreach ($environmentVariables as $line) {
            list($key, $value) = explode('=', $line, 2);
            $envVariables[$key] = $value;
        }
        $params = [
            'name' => uniqid('script_', true),
            'script' => $code,
            'data' => $data,
            'config' => $config,
            'envVariables' => $envVariables,
            'timeout' => $timeout,
        ];
        $body = json_encode($params);
        $servers = self::getNayraAddresses();
        if (!$servers) {
            $this->bringUpNayra();
        }
        $baseUrl = $this->getNayraInstanceUrl();
        $url = $baseUrl . '/run_script';
        $this->ensureNayraServerIsRunning($baseUrl);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpStatus !== 200) {
            $result .= ' HTTP Status: ' . $httpStatus;
            $result .= ' URL: ' . $url;
            $result .= ' BODY: ' . $body;
            Log::error('Error executing script with Nayra Docker', [
                'url' => $url,
                'httpStatus' => $httpStatus,
                'result' => $result,
            ]);
            throw new ScriptException($result);
        }
        return $result;
    }

    private function getNayraInstanceUrl()
    {
        $servers = self::getNayraAddresses();
        return $this->schema . '://' . $servers[0] . ':' . static::$nayraPort;
    }

    private function getDockerLogs($instanceName)
    {
        $docker = Docker::command();
        $logs = [];
        exec($docker . " logs {$instanceName}_nayra 2>&1", $logs, $status);
        if ($status) {
            return 'Error getting logs from Nayra Docker: ' . implode("\n", $logs);
        }
        return implode("\n", $logs);
    }

    /**
     * Ensure that the Nayra server is running.
     *
     * @param string $url URL of the Nayra server
     * @return void
     * @throws ScriptException If cannot connect to Nayra Service
     */
    private function ensureNayraServerIsRunning(string $url)
    {
        $header = @get_headers($url);
        if (!$header) {
            $this->bringUpNayra(true);
        }
    }

    /**
     * Bring up Nayra and check the provided URL.
     *
     * @return void
     */
    private function bringUpNayra($restart = false)
    {
        $docker = Docker::command();
        $instanceName = config('app.instance');
        if (!$restart && $this->findNayraAddresses($docker, $instanceName, 3)) {
            // The container is already running
            return;
        }

        $image = $this->scriptExecutor->dockerImageName();
        //check if image exists
        exec($docker . " inspect {$image} 2>&1", $output, $status);
        if ($status) {
            $this->bringUpNayraContainer();
        } else {

            exec($docker . " stop {$instanceName}_nayra 2>&1 || true");
            exec($docker . " rm {$instanceName}_nayra 2>&1 || true");
            exec(
                $docker . ' run -d --name ' . $instanceName . '_nayra '
                . (config('app.nayra_docker_network')
                    ? '--network=' . config('app.nayra_docker_network') . ' '
                    : '')
                . $image,
                $output,
                $status
            );
            if ($status) {
                Log::error('Error starting Nayra Docker', [
                    'output' => $output,
                    'status' => $status,
                ]);
                throw new ScriptException('Error starting Nayra Docker');
            }
        }
        $this->waitContainerNetwork($docker, $instanceName);
        $url = $this->getNayraInstanceUrl();
        $this->nayraServiceIsRunning($url);
    }

    private function bringUpNayraContainer()
    {
        $lang = Base::NAYRA_LANG;
        Artisan::call("processmaker:build-script-executor {$lang} --rebuild");
    }

    /**
     * Waits for the container network to be ready.
     *
     * @param Docker $docker The Docker instance.
     * @param string $instanceName The name of the container instance.
     */
    private function waitContainerNetwork($docker, $instanceName)
    {
        if (!$this->findNayraAddresses($docker, $instanceName, 30)) {
            throw new ScriptException('Could not get address of the nayra container');
        }
    }

    /**
     * Find the Nayra addresses.
     *
     * @param Docker $docker The Docker instance.
     * @param string $instanceName The name of the container instance.
     * @return bool Returns true if the Nayra addresses were found, false otherwise.
     */
    private function findNayraAddresses($docker, $instanceName, $times): bool
    {
        $ip = '';
        $nayraDockerNetwork = config('app.nayra_docker_network');

        for ($i = 0; $i < $times; $i++) {
            if ($i > 0) {
                sleep(1);
            }
            if ($nayraDockerNetwork === 'host') {
                $ip = exec(
                    $docker . " exec {$instanceName}_nayra hostname -i 2>/dev/null",
                    $output,
                    $status
                );
            } else {
                $ip = exec(
                    $docker . ' inspect --format '
                    . ($nayraDockerNetwork
                        ? "'{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'"
                        : "'{{ .NetworkSettings.IPAddress }}'"
                        )
                    . " {$instanceName}_nayra 2>/dev/null",
                    $output,
                    $status
                );
            }
            if ($status) {
                $ip = '';
            }
            if ($ip) {
                self::setNayraAddresses([$ip]);
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the Nayra service is running.
     *
     * @param string $url The URL of the Nayra service.
     * @return bool Returns true if the Nayra service is running, false otherwise.
     */
    private function nayraServiceIsRunning($url): bool
    {
        for ($i = 0; $i < 30; $i++) {
            if ($i > 0) {
                sleep(1);
            }
            $status = @get_headers($url);
            if ($status) {
                return true;
            }
        }
        throw new ScriptException('Could not connect to the nayra container');
    }

    public static function getNayraAddresses()
    {
        // Check if it is running in unit test mode with Cache ArrayStore
        $isArrayDriver = self::isCacheArrayStore();
        if ($isArrayDriver) {
            return Cache::store('file')->get('nayra_ips');
        }

        return Cache::get('nayra_ips');
    }

    public static function setNayraAddresses(array $addresses)
    {
        // Check if it is running in unit test mode with Cache ArrayStore
        $isArrayDriver = self::isCacheArrayStore();
        if ($isArrayDriver) {
            return Cache::store('file')->forever('nayra_ips', $addresses);
        }

        Cache::forever('nayra_ips', $addresses);
    }

    public static function clearNayraAddresses()
    {
        // Check if it is running in unit test mode with Cache ArrayStore
        $isArrayDriver = self::isCacheArrayStore();
        if ($isArrayDriver) {
            return Cache::store('file')->forget('nayra_ips');
        }

        Cache::forget('nayra_ips');
    }

    private static function isCacheArrayStore(): bool
    {
        $cacheDriver = Cache::getFacadeRoot()->getStore();
        return $cacheDriver instanceof ArrayStore;
    }

    public static function bringUpNayraExecutor(BuildScriptExecutors $builder, string $image)
    {
        $instanceName = config('app.instance');
        $builder->info('Stop existing nayra container');
        $builder->execCommand(Docker::command() . " stop {$instanceName}_nayra 2>&1 || true");
        $builder->execCommand(Docker::command() . " rm {$instanceName}_nayra 2>&1 || true");
        $builder->info('Bring up the nayra container');
        $builder->execCommand(
            Docker::command() . ' run -d --name ' . $instanceName . '_nayra '
            . (config('app.nayra_docker_network')
                ? '--network=' . config('app.nayra_docker_network') . ' '
                : '')
            . $image
        );
        $builder->info('Get IP address of the nayra container');
        $ip = '';
        for ($i = 0; $i < 10; $i++) {
            $ip = exec(
                Docker::command()
                . ' inspect --format '
                . (config('app.nayra_docker_network')
                    ? "'{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'"
                    : "'{{ .NetworkSettings.IPAddress }}'"
                  )
                . " {$instanceName}_nayra 2>/dev/null"
            );
            if ($ip) {
                $builder->info('Nayra container IP: ' . $ip);
                static::setNayraAddresses([$ip]);
                $builder->sendEvent(0, 'done');
                break;
            }
            sleep(1);
        }
        if (!$ip) {
            throw new UnexpectedValueException('Could not get IP address of the nayra container');
        }
    }

    /**
     * Initialize the phpunit test network for Nayra.
     */
    public static function initNayraPhpUnitTest()
    {
        Base::clearNayraAddresses();
        $network = config('app.nayra_docker_network');
        // Check if docker network exists, if not create it
        exec(Docker::command() . " network inspect {$network} 2>&1", $output, $status);
        if ($status) {
            exec(Docker::command() . " network create {$network} 2>&1", $output, $status);
            if ($status) {
                throw new UnexpectedValueException('Could not create docker network');
            }
        }
    }
}
