<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class ScriptCreated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $changes;

    private string $categoryName = '';

    private Script $script;

    /**
     * Create a new event instance.
     *
     * @param Script $script
     * @param array $changes
     */
    public function __construct(Script $script, array $changes)
    {
        $this->script = $script;
        $this->changes = $changes;
        if (isset($this->changes['tmp_script_category_id'])) {
            $this->categoryName = ScriptCategory::getNamesByIds($this->changes['tmp_script_category_id']);
            unset($this->changes['tmp_script_category_id']);
        }
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'script_id' => $this->script->id,
        ];
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        $configCode = isset($this->changes['code']) ? [] : [
            'description' => $this->script->getAttribute('description'),
            'category' => $this->categoryName,
            'language' => $this->script->getAttribute('language'),
        ];

        return array_merge([
            'name' => [
                'label' => $this->script->getAttribute('title'),
                'link' => route('scripts.index'),
            ],
            'created_at' => $this->script->getAttribute('created_at'),
        ], $configCode);
    }

    public function getEventName(): string
    {
        return 'ScriptCreated';
    }
}
