<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class TemplateDeleted implements SecurityLogEventInterface
{
    use Dispatchable;

    private string $templateName;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $templateName)
    {
        $this->templateName = $templateName;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'template_name' => $this->templateName,
            'deleted_at' => Carbon::now()
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'TemplateDeleted';
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'template_name' => $this->templateName
        ];
    }
}
