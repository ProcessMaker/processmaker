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

    protected $code;
    protected $language;
    protected $timeout;
    protected $user;
    protected $data;
    protected $configuration;

    /**
     * Create a new job instance to execute a script.
     *
     * @param string $code
     * @param string $language
     * @param string $timeout
     * @param array $data
     * @param array $configuration
     */
    public function __construct($code, $language, $timeout, User $user, array $data, array $configuration)
    {
        $this->code = $code;
        $this->language = $language;
        $this->timeout = $timeout;
        $this->user = $user;
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
            $script = new Script([
                'code' => $this->code,
                'language' => $this->language,
                'timeout' => $this->timeout,
            ]);
            $response = $script->runScript($this->data, $this->configuration);
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
        $this->user->notify(new ScriptResponseNotification($status, $response));
    }
}
