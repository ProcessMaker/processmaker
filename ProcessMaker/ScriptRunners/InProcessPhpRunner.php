<?php

namespace ProcessMaker\ScriptRunners;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\GenerateAccessToken;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\User;
use RuntimeException;
use Throwable;

/**
 * Run trusted PHP scripts in a local PHP subprocess (no Docker).
 */
class InProcessPhpRunner
{
    /**
     * Execute PHP script code and return the script return value.
     *
     * @param  string  $code
     * @param  array  $data
     * @param  array  $config
     * @param  int  $timeout  Seconds (already capped by caller when needed)
     * @param  User|null  $user
     * @return mixed
     *
     * @throws ScriptTimeoutException
     * @throws ScriptException
     */
    public function run(string $code, array $data, array $config, int $timeout, ?User $user = null)
    {
        if ($timeout <= 0) {
            $timeout = (int) config('core-service-task.default_timeout', 60);
        }

        $workDir = $this->createWorkDir();
        $accessToken = null;

        try {
            file_put_contents($workDir . '/data.json', json_encode($data));
            file_put_contents($workDir . '/config.json', json_encode($config));
            file_put_contents($workDir . '/script.php', $this->normalizeCode($code));

            if ($user) {
                $accessToken = new GenerateAccessToken($user);
            }

            $phpBinary = config('core-service-task.php_binary', PHP_BINARY);
            $memoryLimit = config('core-service-task.memory_limit', '256M');
            $bootstrap = $this->bootstrapPath();

            $env = $this->buildEnvironment($user, $accessToken);

            Log::debug('Executing in-process PHP script', [
                'timeout' => $timeout,
                'php' => $phpBinary,
            ]);

            $result = Process::timeout($timeout)
                ->env($env)
                ->run([
                    $phpBinary,
                    '-d',
                    'memory_limit=' . $memoryLimit,
                    $bootstrap,
                    $workDir,
                ]);

            if (!$result->successful()) {
                $message = trim($result->errorOutput() . "\n" . $result->output());
                throw new ScriptException($message !== '' ? $message : 'In-process PHP script failed');
            }

            $decoded = json_decode($result->output(), true);
            if (!is_array($decoded) || !array_key_exists('output', $decoded)) {
                throw new ScriptException('Invalid in-process script output');
            }

            return $decoded['output'];
        } catch (ProcessTimedOutException $exception) {
            Log::error('In-process PHP script timed out', ['timeout' => $timeout]);
            throw new ScriptTimeoutException(
                __('Script took too long to complete. Consider increasing the timeout.')
                . "\n"
                . __('Timeout: :timeout seconds', ['timeout' => $timeout])
            );
        } catch (ScriptException|ScriptTimeoutException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ScriptException($exception->getMessage(), (int) $exception->getCode(), $exception);
        } finally {
            if ($accessToken) {
                $accessToken->delete();
            }
            $this->removeWorkDir($workDir);
        }
    }

    private function normalizeCode(string $code): string
    {
        $trimmed = ltrim($code);
        if (Str::startsWith($trimmed, '<?php')) {
            return $code;
        }

        return "<?php\n" . $code;
    }

    private function bootstrapPath(): string
    {
        $path = __DIR__ . '/resources/in-process-bootstrap.php';
        if (!file_exists($path)) {
            throw new RuntimeException('In-process bootstrap not found: ' . $path);
        }

        return $path;
    }

    private function createWorkDir(): string
    {
        $base = config('app.processmaker_scripts_home', storage_path('app'));
        $dir = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'in-process-' . Str::uuid();
        if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create in-process work directory');
        }

        return $dir;
    }

    private function removeWorkDir(string $workDir): void
    {
        foreach (['data.json', 'config.json', 'script.php'] as $file) {
            $path = $workDir . '/' . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        if (is_dir($workDir)) {
            @rmdir($workDir);
        }
    }

    /**
     * Build environment for the subprocess (includes parent env + PM vars).
     */
    private function buildEnvironment(?User $user, ?GenerateAccessToken $accessToken): array
    {
        $env = array_merge($_ENV, $_SERVER);
        foreach ($env as $key => $value) {
            if (!is_string($key) || (!is_string($value) && !is_numeric($value))) {
                unset($env[$key]);
            } else {
                $env[$key] = (string) $value;
            }
        }

        EnvironmentVariable::chunk(50, function ($variables) use (&$env) {
            foreach ($variables as $variable) {
                $name = str_replace(' ', '_', $variable->name);
                $env[$name] = (string) $variable->value;
            }
        });

        $env['HOST_URL'] = (string) config('app.docker_host_url');
        $env['APP_URL'] = (string) config('app.docker_host_url');

        if (config('smart-extract.api_host') !== null) {
            $env['SMART_EXTRACT_API_HOST'] = (string) config('smart-extract.api_host');
        }
        if (config('smart-extract.request_timeout') !== null) {
            $env['SMART_EXTRACT_REQUEST_TIMEOUT'] = (string) config('smart-extract.request_timeout');
        }

        if ($user && $accessToken) {
            $env['API_TOKEN'] = $accessToken->getToken();
            $env['API_HOST'] = config('app.docker_host_url') . '/api/1.0';
            $env['API_SSL_VERIFY'] = config('app.api_ssl_verify') ? '1' : '0';
        }

        return $env;
    }
}
