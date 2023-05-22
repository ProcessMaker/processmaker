<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Setting;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class SettingsUpdated implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    private Setting $setting;

    private array $changes;
    private array $original;

    /**
     * Create a new event instance.
     *
     * @param Setting $setting
     * @param array $changes
     */
    public function __construct(Setting $setting, array $changes, array $original)
    {
        $this->setting = $setting;
        $this->changes = $changes;
        $this->original = $original;
    }

    public function getData(): array
    {
        return array_merge([
            'Group' => $this->setting->getAttribute('group'),
            'Name' => $this->setting->getAttribute('name'),
        ], $this->formatChanges($this->changes, $this->original));
    }

    public function getEventName(): string
    {
        return 'SettingsUpdated';
    }
}
