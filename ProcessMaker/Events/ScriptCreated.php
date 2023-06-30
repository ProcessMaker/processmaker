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

    private array $original;

    private Script $script;

    public string $categoryName = '';

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
        $categoryId = $this->script['script_category_id'] ?? '';
        if (!empty($categoryId)) {
            $this->categoryName = ScriptCategory::where('id', $categoryId)->value('name');
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
            'script_id' => $this->script->id
        ];
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        $basic = isset($this->changes['code']) ? [
            'name' => $this->script->getAttribute('title'),
            'created_at' => $this->script->getAttribute('created_at'),
        ] : [
            'name' => $this->script->getAttribute('title'),
            'description' => $this->script->getAttribute('description'),
            'category' => $this->categoryName,
            'language' => $this->script->getAttribute('language')
        ];
        unset($this->changes['code']);
        unset($this->original['code']);

        return array_merge($basic, $this->formatChanges($this->changes, []));
    }

    public function getEventName(): string
    {
        return 'ScriptCreated';
    }
}
