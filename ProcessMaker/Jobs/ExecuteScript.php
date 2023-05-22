<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Contracts\ScriptInterface;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Throwable;

class ExecuteScript implements ShouldQueue
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

    protected $watcher;

    protected $sync;

    /**
     * Create a new job instance to execute a script.
     *
     * @param Script $script
     * @param User $current_user
     * @param string $code
     * @param array $data
     * @param $watcher
     * @param array $configuration
     */
    public function __construct(ScriptInterface $script, User $current_user, $code, array $data, $watcher, array $configuration = [], $sync = false)
    {
        $this->script = $script;
        $this->current_user = $current_user;
        $this->code = $code;
        $this->data = $data;
        $this->configuration = $configuration;
        $this->watcher = $watcher;
        $this->sync = $sync;
    }

    /**
     * Execute the script task.
     *
     * @return void
     */
    public function handle()
    {
        //throw new \Exception('This method must be overridden.');
        try {
            // Just set the code but do not save the object (preview only)
            $this->script->code = $this->code;
            $response = $this->script->runScript($this->data, $this->configuration);
            if ($this->sync) {
                return $response;
            }
            $this->sendResponse(200, $response);
        } catch (Throwable $exception) {
            if ($this->sync) {
                throw $exception;
            }
            $this->sendResponse(500, [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send a response to the user interface
     *
     * @param int $status
     * @param array $response
     */
    private function sendResponse($status, array $response)
    {
        event(new ScriptResponseEvent($this->current_user, $status, $response, $this->watcher));
    }
}
