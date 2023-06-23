<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class TemplateCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $payload;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'type' => $this->payload['type'],
            'version' => $this->payload['version'],
            'name' => $this->payload['name'],
        ];
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return array_merge(
            ['root' => $this->payload['root']],
            $this->getData()
        );
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'TemplateCreated';
    }
}
