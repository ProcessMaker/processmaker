<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\Facades\ScriptRuntime;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\InProcessPhpRunner;
use ProcessMaker\ScriptRuntime\ScriptExecutionContext;
use Throwable;

class TestScript implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    protected $script;

    protected $current_user;

    protected $code;

    protected $data;

    protected $configuration;

    protected $nonce;

    protected bool $runInProcess;

    /**
     * Create a new job instance to execute a script.
     *
     * @param ProcessMaker\Models\Script $script
     * @param ProcessMaker\Models\User $current_user
     * @param string $code
     * @param array $data
     * @param array $configuration
     * @param string|null $nonce
     * @param bool $runInProcess Use Core Service Task in-process runner (no Docker)
     */
    public function __construct(
        Script $script,
        User $current_user,
        $code,
        array $data,
        array $configuration,
        $nonce = null,
        bool $runInProcess = false
    ) {
        $this->script = $script;
        $this->current_user = $current_user;
        $this->code = $code;
        $this->data = $data;
        $this->configuration = $configuration;
        $this->nonce = $nonce;
        $this->runInProcess = $runInProcess;
    }

    /**
     * Execute the script task.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Just set the code but do not save the object (preview only)
            $this->script->code = $this->code;

            if ($this->runInProcess) {
                $response = $this->runInProcessPreview();
                \Log::debug('Response from in-process preview: ' . print_r($response, true));
                $this->sendResponse(200, $response);

                return;
            }

            $metadata = [
                'nonce' => $this->nonce,
                'current_user' => $this->current_user?->id,
            ];
            $response = $this->script->runScript($this->data, $this->configuration, '', null, 0, $metadata);
            \Log::debug('Response from runScript: ' . print_r($response, true));

            if (!config('script-runner-microservice.enabled') ||
                $this->script->scriptExecutor && $this->script->scriptExecutor->type === ScriptExecutorType::Custom) {
                $this->sendResponse(200, $response);
            }
        } catch (Throwable $exception) {
            $this->sendResponse(500, [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Preview using ScriptRuntime modules (default) or bare InProcessPhpRunner.
     *
     * @return array{output: mixed}
     */
    private function runInProcessPreview(): array
    {
        if (!config('core-service-task.enabled')) {
            throw new ConfigurationException(
                'Core Service Task is disabled. Set CORE_SERVICE_TASK_ENABLED=true to enable.'
            );
        }

        if (strtolower((string) $this->script->language) !== 'php') {
            throw new ConfigurationException('In-process preview only supports PHP scripts');
        }

        $user = User::find($this->script->run_as_user_id);
        if (!$user) {
            throw new ConfigurationException('A user is required to run scripts');
        }

        $timeout = (int) ($this->script->timeout ?: config('core-service-task.default_timeout', 60));
        $max = (int) config('core-service-task.max_timeout', 300);
        if ($max > 0 && $timeout > $max) {
            $timeout = $max;
        }
        $timeout = max(1, $timeout);

        if (config('core-service-task.execution', 'modules') === 'modules') {
            $context = new ScriptExecutionContext(
                data: $this->data,
                config: $this->configuration,
                user: $user,
                tokenId: '',
                timeout: $timeout,
                scriptId: (int) $this->script->id,
                source: 'preview',
            );
            $output = ScriptRuntime::run($this->code, $context);
        } else {
            $output = app(InProcessPhpRunner::class)->run(
                $this->code,
                $this->data,
                $this->configuration,
                $timeout,
                $user
            );
        }

        // Match runScript() response shape expected by the Script Editor UI
        return ['output' => $output];
    }

    /**
     * Send a response to the user interface
     *
     * @param int $status
     * @param array $response
     */
    private function sendResponse($status, array $response)
    {
        event(new ScriptResponseEvent($this->current_user, $status, $response, null, $this->nonce));
    }
}
