<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Helpers\ArrayHelper;

class ScreenCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $newScreen;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $newScreen)
    {
        $this->newScreen = $newScreen;

        if (isset($newScreen['screen_category_id'])) {
            $this->newScreen['screen_category'] = ArrayHelper::getNamesByIds('ScreenCategory', $newScreen['screen_category_id'], 'name');
            unset($this->newScreen['screen_category_id']);
        }
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'name' => $this->newScreen['title'] ?? '',
            'description' => $this->newScreen['description'] ?? '',
            'type' => $this->newScreen['type'] ?? '',
            'screen_category' => $this->newScreen['screen_category']
        ];
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->newScreen['id'] ?? '',
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
}
