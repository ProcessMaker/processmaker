<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Models\Message;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface;

class MessageEventThrown implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        private ProcessRequest $processRequest,
        private EntityInterface $node,
        private Message $message
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('ProcessMaker.Models.ProcessRequest.' . $this->processRequest->getKey()),
        ];
        $hasParent = $this->processRequest->parent_request_id;
        if ($hasParent) {
            $channels[] = new PrivateChannel('ProcessMaker.Models.ProcessRequest.' . $this->processRequest->parent_request_id . '.SubProcesses');
        }
        return $channels;
    }

    /**
     * Set the event name
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'MessageEventThrown';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event' => [
                'id' => $this->message->getId(),
                'name' => $this->message->getName(),
            ],
            'element' => [
                'id' => $this->node->getId(),
                'name' => $this->node->getName(),
            ],
            'data' => (object) $this->message->getData($this->processRequest),
        ];
    }

    public function getProcessRequest()
    {
        return $this->processRequest;
    }

    public function getNode()
    {
        return $this->node;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
