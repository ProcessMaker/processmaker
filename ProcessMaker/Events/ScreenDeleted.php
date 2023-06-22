<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Screen;

class ScreenDeleted implements SecurityLogEventInterface
{
    use Dispatchable;
    private Screen $screen;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Screen $screen)
    {
        $this->screen =  $screen;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'title' => $this->screen->getAttributes()['title'],
            'description' => $this->screen->getAttributes()['description'],
            'deleted_at' => Carbon::now()
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
            ['id' => $this->screen->getAttribute('id')],
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
        return 'ScreenDeleted';
    }
}
