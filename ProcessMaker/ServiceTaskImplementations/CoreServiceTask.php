<?php

namespace ProcessMaker\ServiceTaskImplementations;

use ProcessMaker\Contracts\ServiceTaskImplementationInterface;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\Facades\ScriptRuntime;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\InProcessPhpRunner;
use ProcessMaker\ScriptRuntime\ScriptExecutionContext;

/**
 * ServiceTask implementation that runs allowlisted PHP scripts in-process
 * without Docker or the script microservice.
 *
 * execution=modules (default): Laravel worker + ScriptRuntime modules.
 * execution=bare: PHP subprocess without Laravel (legacy).
 */
class CoreServiceTask implements ServiceTaskImplementationInterface
{
    public const IMPLEMENTATION = 'processmaker/core-service-task';

    /**
     * @param  array  $data
     * @param  array  $config  Must include script_id
     * @param  string  $tokenId
     * @param  int|float  $timeout
     * @return array
     *
     * @throws ConfigurationException
     */
    public function run(array $data, array $config, $tokenId = '', $timeout = 0)
    {
        if (!config('core-service-task.enabled')) {
            throw new ConfigurationException('Core Service Task is disabled. Set CORE_SERVICE_TASK_ENABLED=true to enable.');
        }

        $scriptId = $config['script_id'] ?? null;
        if (empty($scriptId)) {
            throw new ConfigurationException('Core Service Task requires config.script_id');
        }

        $script = Script::find($scriptId);
        if (!$script) {
            throw new ConfigurationException('Script "' . $scriptId . '" not found');
        }

        if (!$script->allow_in_process) {
            throw new ConfigurationException(
                'Script "' . $script->id . '" is not allowed for in-process execution. Enable allow_in_process on the script.'
            );
        }

        if (strtolower((string) $script->language) !== 'php') {
            throw new ConfigurationException('Core Service Task only supports PHP scripts');
        }

        $user = User::find($script->run_as_user_id);
        if (!$user) {
            throw new ConfigurationException('A user is required to run scripts');
        }

        $effectiveTimeout = $this->resolveTimeout((int) $timeout, (int) $script->timeout);

        if ($this->usesModulesExecution()) {
            $context = new ScriptExecutionContext(
                data: $data,
                config: $config,
                user: $user,
                tokenId: (string) $tokenId,
                timeout: $effectiveTimeout,
                scriptId: (int) $script->id,
                source: 'service-task',
            );

            $output = ScriptRuntime::run($script->code, $context);

            return ScriptRuntime::normalizeOutput($output);
        }

        $output = app(InProcessPhpRunner::class)->run(
            $script->code,
            $data,
            $config,
            $effectiveTimeout,
            $user
        );

        return ScriptRuntime::normalizeOutput($output);
    }

    private function usesModulesExecution(): bool
    {
        return config('core-service-task.execution', 'modules') === 'modules';
    }

    /**
     * Precedence: requested timeout > script timeout > default; then hard cap.
     */
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
