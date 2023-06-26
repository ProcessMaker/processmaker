<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessTemplates;

class TemplateDeleted implements SecurityLogEventInterface
{
    use Dispatchable;

    private ProcessTemplates $template;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessTemplates $template)
    {
        $this->template = $template;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'template_name' => $this->template->name,
            'deleted_at' => Carbon::now(),
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
            'id' => $this->template->id,
            'template_name' => $this->template->name,
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
}
