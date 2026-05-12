<?php

namespace ProcessMaker\Models;

use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Console\Commands\BuildScriptExecutors;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Facades\Docker;
use ProcessMaker\ScriptRunners\Base;
use ProcessMaker\Models\ScriptExecutor;
use UnexpectedValueException;

/**
 * Execute a docker container copying files to interchange information.
 */
trait ScriptDockerNayraTrait
{

    private $schema = 'http';

    abstract protected function getScriptExecutor(): ScriptExecutor;

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
        $baseUrl = $this->ensureNayraServerIsRunning($this->resolveNayraBaseUrl());
        $url = $baseUrl . '/run_script';

        $ch = $this->curlInit($url);
        $this->curlSetOpt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        $this->curlSetOpt($ch, CURLOPT_POSTFIELDS, $body);
        $this->curlSetOpt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->curlSetOpt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
        ]);
        $result = $this->curlExec($ch);
        $httpStatus = $this->curlGetInfo($ch, CURLINFO_HTTP_CODE);
        $this->curlClose($ch);
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
        if (config('app.nayra_rest_api_host')) {
            return $this->normalizeNayraUrl(config('app.nayra_rest_api_host'));
        }

        if ($endpoint = self::getNayraEndpoint()) {
            return $endpoint;
        }

        return $this->buildNayraEndpoint();
    }

    private function resolveNayraBaseUrl()
    {
        if (config('app.nayra_rest_api_host')) {
            return $this->normalizeNayraUrl(config('app.nayra_rest_api_host'));
        }

        $endpoint = self::getNayraEndpoint();
        if ($endpoint) {
            if ($this->isNayraServiceReachable($endpoint)) {
                return $endpoint;
            }

            self::clearNayraEndpoint();
        }

        $this->bringUpNayra();

        return $this->getNayraInstanceUrl();
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
     * @return string
     * @throws ScriptException If cannot connect to Nayra Service
     */
    private function ensureNayraServerIsRunning(string $url): string
    {
        if ($this->isNayraServiceReachable($url)) {
            return $url;
        }

        if (config('app.nayra_rest_api_host')) {
            throw new ScriptException('Could not connect to the configured Nayra REST API host: ' . $url);
        }

        self::clearNayraEndpoint();
        $this->bringUpNayra(true);

        $url = $this->getNayraInstanceUrl();
        $this->nayraServiceIsRunning($url);

        return $url;
    }

    /**
     * Bring up Nayra and check the provided URL.
     *
     * @return void
     */
    private function bringUpNayra($restart = false)
    {
        $docker = Docker::command();
        $instanceName = self::getNayraContainerName();
        $endpoint = $this->buildNayraEndpoint();

        if (!$restart && $this->isNayraServiceReachable($endpoint)) {
            self::setNayraEndpoint($endpoint);
            return;
        }

        $image = $this->getNayraDockerImage($docker);
        $portMapping = $this->getNayraDockerPortMapping();
        $network = config('app.nayra_docker_network');

        $output = [];
        exec($docker . " stop {$instanceName}_nayra 2>&1 || true", $output);

        $output = [];
        exec($docker . " rm {$instanceName}_nayra 2>&1 || true", $output);

        $output = [];
        exec(
            $docker . ' run -d '
            . $portMapping
            . '--name ' . $instanceName . '_nayra '
            . ($network ? '--network=' . $network . ' ' : '')
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

        $this->cacheNayraEndpointAfterReadiness($endpoint);
    }

    private function cacheNayraEndpointAfterReadiness(string $endpoint): void
    {
        $this->nayraServiceIsRunning($endpoint);
        self::setNayraEndpoint($endpoint);
    }

    private function bringUpNayraContainer()
    {
        $lang = Base::NAYRA_LANG;
        Artisan::call("processmaker:build-script-executor {$lang} --rebuild");
    }

    private function getNayraDockerImage(string $docker): string
    {
        $image = $this->getScriptExecutor()->dockerImageName();
        $sharedImage = $this->sharedNayraDockerImageName($image);

        foreach (array_unique([$sharedImage, $image]) as $candidate) {
            $output = [];
            exec($docker . " inspect {$candidate} 2>&1", $output, $status);
            if (!$status) {
                return $candidate;
            }
        }

        $this->bringUpNayraContainer();

        return $image;
    }

    private function sharedNayraDockerImageName(string $image): string
    {
        $currentInstance = config('app.instance');
        $sharedInstance = self::getNayraInstanceName();

        if ($currentInstance === $sharedInstance) {
            return $image;
        }

        return str_replace(
            'executor-' . $currentInstance . '-',
            'executor-' . $sharedInstance . '-',
            $image
        );
    }

    /**
     * Waits for the container network to be ready.
     *
     * @param Docker $docker The Docker instance.
     * @param string $instanceName The name of the container instance.
     */
    private function waitContainerNetwork($docker, $instanceName)
    {
        if (!self::findNayraAddresses($docker, $instanceName, 30)) {
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
    private static function findNayraAddresses($docker, $instanceName, $times): bool
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
                $ip = explode(' ', trim($ip))[0];
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
        for ($i = 0; $i < static::getNayraEndpointReadinessAttempts(); $i++) {
            if ($i > 0) {
                sleep(1);
            }
            $status = $this->getHeaders($url);
            if ($status) {
                return true;
            }
        }
        throw new ScriptException('Could not connect to the nayra container');
    }

    private function isNayraServiceReachable(string $url): bool
    {
        return (bool) $this->getHeaders($url);
    }

    protected function getHeaders(string $url): array|false
    {
        return static::getNayraEndpointHeaders($url);
    }

    protected function curlInit(string $url): mixed
    {
        return curl_init($url);
    }

    protected function curlSetOpt(mixed $handle, int $option, mixed $value): bool
    {
        return curl_setopt($handle, $option, $value);
    }

    protected function curlExec(mixed $handle): string|bool
    {
        return curl_exec($handle);
    }

    protected function curlGetInfo(mixed $handle, int $option): mixed
    {
        return curl_getinfo($handle, $option);
    }

    protected function curlClose(mixed $handle): void
    {
        curl_close($handle);
    }

    private function normalizeNayraUrl(string $url): string
    {
        return rtrim($url, '/');
    }

    private function buildNayraEndpoint(): string
    {
        return self::buildNayraEndpointUrl($this->schema);
    }

    private static function buildNayraEndpointUrl(string $schema = 'http'): string
    {
        return $schema . '://' . self::getNayraEndpointHost() . ':' . self::getNayraPortValue();
    }

    private static function getNayraEndpointHost(): string
    {
        $dockerHost = config('app.processmaker_scripts_docker_host');
        if ($dockerHost) {
            $parsed = parse_url($dockerHost);
            if (isset($parsed['host'])) {
                return $parsed['host'];
            }

            return explode(':', $dockerHost)[0];
        }

        return '127.0.0.1';
    }

    private function getNayraDockerPortMapping(): string
    {
        return self::getNayraDockerPortMappingValue();
    }

    private static function getNayraDockerPortMappingValue(): string
    {
        $port = self::getNayraPortValue();

        return config('app.nayra_docker_network') === 'host'
            ? '-e PORT=' . $port . ' '
            : '-p ' . $port . ':8080 ';
    }

    private static function getNayraInstanceName(): string
    {
        $instance = config('app.instance');

        if (!config('app.multitenancy')) {
            return $instance;
        }

        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if ($tenant && str_ends_with($instance, '_' . $tenant->id)) {
            return substr($instance, 0, -strlen('_' . $tenant->id));
        }

        return $instance;
    }

    private static function getNayraContainerName(): string
    {
        return self::getNayraInstanceName();
    }

    public static function getNayraEndpoint()
    {
        // Check if it is running in unit test mode with Cache ArrayStore
        $isArrayDriver = self::isCacheArrayStore();
        if ($isArrayDriver) {
            return Cache::store('file')->get('nayra_endpoint');
        }

        return Cache::get('nayra_endpoint');
    }

    public static function setNayraEndpoint(string $endpoint)
    {
        // Check if it is running in unit test mode with Cache ArrayStore
        $isArrayDriver = self::isCacheArrayStore();
        if ($isArrayDriver) {
            return Cache::store('file')->forever('nayra_endpoint', $endpoint);
        }

        Cache::forever('nayra_endpoint', $endpoint);
    }

    public static function clearNayraEndpoint()
    {
        // Check if it is running in unit test mode with Cache ArrayStore
        $isArrayDriver = self::isCacheArrayStore();
        if ($isArrayDriver) {
            return Cache::store('file')->forget('nayra_endpoint');
        }

        Cache::forget('nayra_endpoint');
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
        $instanceName = self::getNayraContainerName();
        $docker = Docker::command();
        $builder->info('Stop existing nayra container');
        $builder->execCommand("{$docker} stop {$instanceName}_nayra 2>&1 || true");
        $builder->execCommand("{$docker} rm {$instanceName}_nayra 2>&1 || true");
        $builder->info('Bring up the nayra container');
        $builder->execCommand(
            $docker . ' run -d '
            . self::getNayraDockerPortMappingValue()
            . '--name ' . $instanceName . '_nayra '
            . (config('app.nayra_docker_network')
                ? '--network=' . config('app.nayra_docker_network') . ' '
                : '')
            . $image
        );
        $endpoint = self::buildNayraEndpointUrl();
        if (!static::nayraEndpointIsRunning($endpoint)) {
            throw new UnexpectedValueException('Could not connect to the nayra container');
        }

        self::setNayraEndpoint($endpoint);
        $builder->info('Nayra endpoint: ' . $endpoint);
        $builder->sendEvent(0, 'done');
    }

    private static function nayraEndpointIsRunning(string $url): bool
    {
        for ($i = 0; $i < static::getNayraEndpointReadinessAttempts(); $i++) {
            if ($i > 0) {
                sleep(1);
            }
            if (static::getNayraEndpointHeaders($url)) {
                return true;
            }
        }

        return false;
    }

    protected static function getNayraEndpointHeaders(string $url): array|false
    {
        return @get_headers($url);
    }

    protected static function getNayraEndpointReadinessAttempts(): int
    {
        return 30;
    }

    /**
     * Initialize the phpunit test network for Nayra.
     */
    public static function initNayraPhpUnitTest()
    {
        Base::clearNayraAddresses();
        Base::clearNayraEndpoint();
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

    private function getNayraPort()
    {
        return self::getNayraPortValue();
    }

    private static function getNayraPortValue(): int
    {
        return (int) config('app.nayra_port', 8080) ?: 8080;
    }
}
