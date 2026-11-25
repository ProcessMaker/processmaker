<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\ProcessRequestToken;

class ActivityAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payloadUrl;

    /**
     * True when the task was reassigned (or escalated to manager), false for
     * initial activation, self-service claim, rollback, etc.
     */
    public bool $reassignment;

    private $processRequest;

    private $processRequestToken;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessRequestToken $token, bool $reassignment = false)
    {
        $this->payloadUrl = route('api.tasks.show', ['task' => $token->id]);
        $this->reassignment = $reassignment;
        $this->processRequest = $token->processRequest;
        $this->processRequestToken = $token;
    }

    /**
     * Set the event name
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'ActivityAssigned';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ProcessMaker.Models.ProcessRequest.' . $this->processRequest->getKey());
    }

    /**
     * Return the process request.
     *
     * @return \ProcessMaker\Models\ProcessRequest
     */
    public function getProcessRequest()
    {
        return $this->processRequest;
    }

    /**
     * Return the process request token.
     *
     * @return \ProcessMaker\Models\ProcessRequestToken
     */
    public function getProcessRequestToken()
    {
        return $this->processRequestToken;
    }

    /**
     * Get data
     */
    public function getData(): array
    {
        return [
            'payloadUrl' => $this->payloadUrl,
            'element_type' => $this->processRequestToken->element_type,
            'reassignment' => $this->reassignment,
        ];
    }

    /**
     * Whether this assignment was a reassignment (or manager escalation).
     */
    public function isReassignment(): bool
    {
        return $this->reassignment;
    }
}
