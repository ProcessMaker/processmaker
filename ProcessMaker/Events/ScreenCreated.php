<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ScreenCategory;

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

        if (isset($newScreen['tmp_screen_category_id'])) {
            $this->newScreen['screen_category'] = ScreenCategory::getNamesByIds($newScreen['tmp_screen_category_id']);
            unset($newScreen['tmp_screen_category_id']);
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
            'name' => [
                'label' => $this->newScreen['title'],
                'link' => route('screen-builder.edit', ['screen' => $this->newScreen['id']]),
            ],
            'description' => $this->newScreen['description'] ?? '',
            'type' => $this->newScreen['type'] ?? '',
            'Screen Categories' => $this->newScreen['screen_category'] ?? '',
            'created_at' => $this->newScreen['created_at'] ?? Carbon::now(),
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
