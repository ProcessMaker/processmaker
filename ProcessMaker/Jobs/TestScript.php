<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Services\ScriptMicroserviceService;
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

    /**
     * Create a new job instance to execute a script.
     *
     * @param Script $script
     * @param User $current_user
     * @param string $code
     * @param array $data
     * @param array $configuration
     */
    public function __construct(Script $script, User $current_user, $code, array $data, array $configuration, $nonce = null)
    {
        $this->script = $script;
        $this->current_user = $current_user;
        $this->code = $code;
        $this->data = $data;
        $this->configuration = $configuration;
        $this->nonce = $nonce;
    }

    /**
     * Execute the script task.
     *
     * @return void
     */
    public function handle()
    {
        $startTime = microtime(true);
        try {
            // Just set the code but do not save the object (preview only)
            $this->script->code = $this->code;
            $metadata = [
                'nonce' => $this->nonce,
                'current_user' => $this->current_user?->id,
                'start_time' => microtime(true),
            ];
            $response = $this->script->runScript($this->data, $this->configuration, '', null, 0, $metadata);
            Log::debug('Response from runScript: ' . print_r($response, true));

            if ($this->script->scriptExecutor->type === ScriptExecutorType::Realtime) {
                // Realtime executors return the microservice payload synchronously rather than
                // posting back to the callback endpoint, so format it the same way here.
                $formatted = (new ScriptMicroserviceService())->formatPreviewResponse($response);
                $this->sendResponse($formatted['status'], $formatted['output'], (microtime(true) - $startTime));
            } elseif (!config('script-runner-microservice.enabled')) {
                $this->sendResponse(200, $response, (microtime(true) - $startTime));
            }
        } catch (Throwable $exception) {
            $this->sendResponse(500, [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ], (microtime(true) - $startTime));
        }
    }

    /**
     * Send a response to the user interface
     *
     * @param int $status
     * @param array $response
     */
    private function sendResponse($status, array $response, float $duration)
    {
        event(new ScriptResponseEvent($this->current_user, $status, $response, null, $this->nonce, $duration));
    }
}
