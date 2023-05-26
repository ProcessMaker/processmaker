<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class ScreenCreated implements SecurityLogEventInterface
{
    use Dispatchable;
    private $newScreen;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $newScreen)
    {
        $this->newScreen =  $newScreen;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {

        return [
            'name' => $this->newScreen['title'],
            'description' => $this->newScreen['description'],
            'type' => $this->newScreen['type'],
            'screen_category_id' => $this->newScreen['screen_category_id']
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'ScreenCreated';
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'name' => $this->newScreen['title'],
            'description' => $this->newScreen['description'],
            'type' => $this->newScreen['type'],
            'screen_category_id' => $this->newScreen['screen_category_id']
        ];
    }
}
