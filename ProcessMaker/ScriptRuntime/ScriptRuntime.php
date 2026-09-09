<?php

namespace ProcessMaker\ScriptRuntime;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ProcessMaker\Contracts\ScriptModuleInterface;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Models\EnvironmentVariable;
use RuntimeException;
use Throwable;

/**
 * Executes trusted PHP scripts in the current Laravel process with registered modules.
 */
class ScriptRuntime
{
    public function __construct(
        private ScriptModuleRegistry $registry
    ) {
    }

    /**
     * Register a module (convenience for packages).
     *
     * @param  class-string<ScriptModuleInterface>  $moduleClass
     */
    public function registerModule(string $moduleClass, ?string $key = null): void
    {
        $this->registry->register($moduleClass, $key);
    }

    public function registry(): ScriptModuleRegistry
    {
        return $this->registry;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function catalog(): array
    {
        return $this->registry->catalog();
    }

    /**
     * Run script code with $data, $config, $modules and per-module variables.
     *
     * Also exposes Docker-compatible env vars for the duration of the run:
     * API_TOKEN, API_HOST, APP_URL, HOST_URL, EnvironmentVariable rows, etc.
     * Readable via getenv('API_TOKEN') inside the script.
     *
     * @return mixed Script return value
     *
     * @throws ScriptException
     */
    public function run(string $code, ScriptExecutionContext $context): mixed
    {
        $instances = $this->instantiateModules($context);
        $scriptPath = $this->writeTempScript($code);
        $accessToken = null;
        $envSnapshot = null;

        try {
            if ($context->user) {
                $accessToken = new GenerateAccessToken($context->user);
            }

            $envSnapshot = $this->applyScriptEnvironment($context, $accessToken);

            Log::debug('Executing script via ScriptRuntime (modules)', [
                'script_id' => $context->scriptId,
                'source' => $context->source,
                'modules' => array_keys($instances),
            ]);

            $result = (static function (string $__scriptPath, ScriptExecutionContext $__context, array $__instances) {
                $data = $__context->data;
                $config = $__context->config;
                $modules = $__instances;
                extract($__instances, EXTR_SKIP);

                return include $__scriptPath;
            })($scriptPath, $context, $instances);

            return $result;
        } catch (Throwable $exception) {
            throw new ScriptException($exception->getMessage(), (int) $exception->getCode(), $exception);
        } finally {
            if ($envSnapshot !== null) {
                $this->restoreScriptEnvironment($envSnapshot);
            }
            if ($accessToken) {
                $accessToken->delete();
            }
            if (is_file($scriptPath)) {
                @unlink($scriptPath);
            }
        }
    }

    /**
     * Normalize script return to an array for process data merge.
     */
    public function normalizeOutput(mixed $output): array
    {
        if ($output === null) {
            return [];
        }

        if (!is_array($output)) {
            return ['response' => $output];
        }

        return $output;
    }

    /**
     * @return array<string, ScriptModuleInterface>
     */
    private function instantiateModules(ScriptExecutionContext $context): array
    {
        $instances = [];
        foreach ($this->registry->all() as $key => $class) {
            /** @var ScriptModuleInterface $module */
            $module = app()->make($class);
            $module->boot($context);
            $instances[$key] = $module;
        }

        return $instances;
    }

    /**
     * Apply script env vars (same contract as Docker / bare InProcessPhpRunner).
     *
     * @return array{previous: array<string, string|false>, keys: list<string>}
     */
    private function applyScriptEnvironment(ScriptExecutionContext $context, ?GenerateAccessToken $accessToken): array
    {
        $values = [];

        EnvironmentVariable::chunk(50, function ($variables) use (&$values) {
            foreach ($variables as $variable) {
                $name = str_replace(' ', '_', $variable->name);
                $values[$name] = (string) $variable->value;
            }
        });

        $hostUrl = (string) config('app.docker_host_url');
        $values['HOST_URL'] = $hostUrl;
        $values['APP_URL'] = $hostUrl;

        if (config('smart-extract.api_host') !== null) {
            $values['SMART_EXTRACT_API_HOST'] = (string) config('smart-extract.api_host');
        }
        if (config('smart-extract.request_timeout') !== null) {
            $values['SMART_EXTRACT_REQUEST_TIMEOUT'] = (string) config('smart-extract.request_timeout');
        }

        if ($context->user && $accessToken) {
            $values['API_TOKEN'] = $accessToken->getToken();
            $values['API_HOST'] = $hostUrl . '/api/1.0';
            $values['API_SSL_VERIFY'] = config('app.api_ssl_verify') ? '1' : '0';
        }

        $previous = [];
        foreach ($values as $name => $value) {
            $existing = getenv($name);
            $previous[$name] = $existing === false ? false : (string) $existing;
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }

        return [
            'previous' => $previous,
            'keys' => array_keys($values),
        ];
    }

    /**
     * @param  array{previous: array<string, string|false>, keys: list<string>}  $snapshot
     */
    private function restoreScriptEnvironment(array $snapshot): void
    {
        foreach ($snapshot['keys'] as $name) {
            $previous = $snapshot['previous'][$name] ?? false;
            if ($previous === false) {
                putenv($name);
                unset($_ENV[$name], $_SERVER[$name]);
            } else {
                putenv($name . '=' . $previous);
                $_ENV[$name] = $previous;
                $_SERVER[$name] = $previous;
            }
        }
    }

    private function writeTempScript(string $code): string
    {
        $base = config('app.processmaker_scripts_home', storage_path('app'));
        $dir = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'script-runtime';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create script-runtime work directory');
        }

        $path = $dir . DIRECTORY_SEPARATOR . 'script-' . Str::uuid() . '.php';
        $normalized = $this->normalizeCode($code);
        if (file_put_contents($path, $normalized) === false) {
            throw new RuntimeException('Unable to write temporary script file');
        }

        return $path;
    }

    private function normalizeCode(string $code): string
    {
        $trimmed = ltrim($code);
        if (Str::startsWith($trimmed, '<?php')) {
            return $code;
        }

        return "<?php\n" . $code;
    }
}
