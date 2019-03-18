<?php

namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use ProcessMaker\Models\Script;
use Throwable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Notifications\ScriptResponseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use ProcessMaker\Models\User;

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

    /**
     * Create a new job instance to execute a script.
     *
     * @param ProcessMaker\Models\Script $script
     * @param ProcessMaker\Models\User $current_user
     * @param string $code
     * @param array $data
     * @param array $configuration
     */
    public function __construct(Script $script, User $current_user, $code, array $data, array $configuration)
    {
        $this->script = $script;
        $this->current_user = $current_user;
        $this->code = $code;
        $this->data = $data;
        $this->configuration = $configuration;
    }

    /**
     * Execute the script task.
     *
     * @return void
     */
    public function handle()
    {
        try {
            # Just set the code but do not save the object (preview only)
            $this->script->code = $this->code;
            $response = $this->script->runScript($this->data, $this->configuration);
            $this->sendResponse(200, $response);
        } catch (Throwable $exception) {
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
        $this->current_user->notify(new ScriptResponseNotification($status, $response));
    }
}
