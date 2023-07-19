<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessCategory;

class TemplatePublished implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $newTemplate;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $newTemplate)
    {
        $this->newTemplate = $newTemplate;

        if (isset($newTemplate['process_category_id'])) {
            $this->newTemplate['process_category'] = ProcessCategory::getNamesByIds($newTemplate['process_category_id']);
            unset($newTemplate['process_category_id']);
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
                'label' => $this->newTemplate['name'],
                'link' => route('processes.index') . '#nav-categories',
            ],
            'description' => $this->newTemplate['description'] ?? '',
            'save assets mode' => $this->newTemplate['saveAssetsMode'] ?? '',
            'Template Categories' => $this->newTemplate['process_category'] ?? '',
            'created_at' => $this->newTemplate['created_at'] ?? Carbon::now(),
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
            'id' => $this->newTemplate['asset_id'] ?? '',
        ];
    }

    /**
     * Get the Event name with the syntax ‘[Past-test Action] [Object]’
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'TemplatePublished';
    }
}
