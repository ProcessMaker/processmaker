<?php

namespace ProcessMaker\ScriptRunners;

use Illuminate\Contracts\Container\BindingResolutionException;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Facades\ScriptRuntime;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRuntime\ScriptExecutionContext;

class ScriptRunner
{
    /**
     * Concrete script runner (Docker / microservice / mock).
     *
     * @var Base|ScriptMicroserviceRunner|MockRunner
     */
    private $runner;

    private string $tokenId = '';

    public function __construct(protected Script $script)
    {
        $this->runner = $this->getScriptRunner($this->script->scriptExecutor);
    }

    /**
     * Run a script code.
     *
     * When core-service-task is enabled and the script has allow_in_process=true (PHP),
     * runs via ScriptRuntime / InProcessPhpRunner and skips Docker/microservice.
     *
     * @param string $code
     * @param array $data
     * @param array $config
     * @param int $timeout
     * @param User $user
     * @param mixed $sync
     * @param array $metadata
     *
     * @return array{output: mixed}
     *
     * @throws \RuntimeException
     */
    public function run($code, array $data, array $config, $timeout, $user, $sync, $metadata)
    {
        if ($this->shouldRunInProcess()) {
            return $this->runInProcess($code, $data, $config, $timeout, $user);
        }

        return $this->runner->run($code, $data, $config, $timeout, $user, $sync, $metadata);
    }

    /**
     * Get a runner instance from executor
     *
     * @param ScriptExecutor $executor
     *
     * @return Base|ScriptMicroserviceRunner|MockRunner
     * @throws ScriptLanguageNotSupported
     * @throws BindingResolutionException
     */
    private function getScriptRunner(ScriptExecutor $executor): Base|ScriptMicroserviceRunner|MockRunner
    {
        if (!config('script-runner-microservice.enabled') || $executor->type === ScriptExecutorType::Custom) {
            $language = strtolower($executor->language);
            $runner = config("script-runners.{$language}.runner");
            if (!$runner) {
                throw new ScriptLanguageNotSupported($language);
            } else {
                $class = "ProcessMaker\\ScriptRunners\\{$runner}";

                return app()->make($class, ['scriptExecutor' => $executor]);
            }
        } else {
            return new ScriptMicroserviceRunner($this->script);
        }
    }

    /**
     * Set the tokenId of reference.
     *
     * @param string $tokenId
     *
     * @return void
     */
    public function setTokenId($tokenId)
    {
        $this->tokenId = (string) $tokenId;
        if (method_exists($this->runner, 'setTokenId')) {
            $this->runner->setTokenId($tokenId);
        }
    }

    private function shouldRunInProcess(): bool
    {
        if (!config('core-service-task.enabled')) {
            return false;
        }

        if (!$this->script->allow_in_process) {
            return false;
        }

        return strtolower((string) $this->script->language) === 'php';
    }

    /**
     * @return array{output: mixed}
     */
    private function runInProcess($code, array $data, array $config, $timeout, ?User $user): array
    {
        $effectiveTimeout = $this->resolveTimeout((int) $timeout, (int) $this->script->timeout);

        if (config('core-service-task.execution', 'modules') === 'modules') {
            $context = new ScriptExecutionContext(
                data: $data,
                config: $config,
                user: $user,
                tokenId: $this->tokenId,
                timeout: $effectiveTimeout,
                scriptId: (int) $this->script->id,
                source: 'script-task',
            );

            $output = ScriptRuntime::run($code, $context);
        } else {
            $output = app(InProcessPhpRunner::class)->run(
                $code,
                $data,
                $config,
                $effectiveTimeout,
                $user
            );
        }

        // Match Docker / runScript() response shape
        return ['output' => ScriptRuntime::normalizeOutput($output)];
    }

    private function resolveTimeout(int $requestedTimeout, int $scriptTimeout): int
    {
        $timeout = $requestedTimeout > 0
            ? $requestedTimeout
            : ($scriptTimeout > 0 ? $scriptTimeout : (int) config('core-service-task.default_timeout', 60));

        $max = (int) config('core-service-task.max_timeout', 300);
        if ($max > 0 && $timeout > $max) {
            return $max;
        }

        return max(1, $timeout);
    }
}
