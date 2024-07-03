<?php

namespace ProcessMaker\Models;

use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Facades\Docker;
use ProcessMaker\ScriptRunners\Base;

/**
 * Execute a docker container copying files to interchange information.
 */
trait ScriptDockerNayraTrait
{

    private $inHost = true;

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
            $this->bringUpNayraContainer();
            $servers = self::getNayraAddresses();
        }
        $index = array_rand($servers);
        $url = 'http://' . $servers[$index] . ':8080/run_script';
        $this->ensureNayraServerIsRunning('http://' . $servers[$index] . ':8080');
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
            error_log('Error executing script with Nayra Docker: ' . $result);
            $result .= "\n" . $this->getDockerLogs(config('app.instance'));
            Log::error('Error executing script with Nayra Docker', [
                'url' => $url,
                'httpStatus' => $httpStatus,
                'result' => $result,
            ]);
            throw new ScriptException($result);
        }
        return $result;
    }

    private function getDockerLogs($instanceName)
    {
        $docker = Docker::command();
        $logs = [];
        exec($docker . " logs {$instanceName}_nayra", $logs, $status);
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
            $this->bringUpNayra($url);
        }
    }

    /**
     * Bring up Nayra and check the provided URL.
     *
     * @param string $url The URL to check
     * @return void
     */
    private function bringUpNayra(string $url)
    {
        $docker = Docker::command();
        $instanceName = config('app.instance');
        if ($this->findNayraAddresses($docker, $instanceName, 3)) {
            // The container is already running
            return;
        }

        $image = $this->scriptExecutor->dockerImageName();
        exec($docker . " stop {$instanceName}_nayra 2>&1 || true");
        exec($docker . " rm {$instanceName}_nayra 2>&1 || true");
        exec(
            $docker . ' run -d --name ' . $instanceName . '_nayra '
            . '-p 8080:8080 '
            . ($this->inHost ? '--network host ': '')
            . $image . ' &',
            $output,
            $status
        );
        if ($status) {
            error_log('Error starting Nayra Docker: ' . implode("\n", $output));
            Log::error('Error starting Nayra Docker', [
                'output' => $output,
                'status' => $status,
            ]);
            throw new ScriptException('Error starting Nayra Docker');
        }
        $this->waitContainerNetwork($docker, $instanceName);
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
            error_log('Could not get address of the nayra container');
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
        for ($i = 0; $i < $times; $i++) {
            if ($i > 0) {
                sleep(1);
            }
            $ip = exec($docker . " inspect --format '{{ .NetworkSettings.IPAddress }}' {$instanceName}_nayra");
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
        error_log('Could not connect to the nayra container: ' . $url);
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

    private static function isCacheArrayStore(): bool
    {
        $cacheDriver = Cache::getFacadeRoot()->getStore();
        return $cacheDriver instanceof ArrayStore;
    }
}
