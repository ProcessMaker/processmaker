<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Helpers\SensitiveDataHelper;
use ProcessMaker\Models\Setting;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class SettingsUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

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
        // Verify if the config is a sensitive value
        $key = $this->setting->getAttribute('key');
        if (SensitiveDataHelper::isSensitiveKey($key)) {
            $this->changes['config'] = SensitiveDataHelper::parseString($this->changes['config']);
            $this->original['config'] = SensitiveDataHelper::parseString($this->original['config']);
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
            'setting_id' => $this->setting->id
        ];
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge([
            'group' => $this->setting->getAttribute('group'),
            'name' => $this->setting->getAttribute('name'),
            'last_modified' => $this->setting->getAttribute('updated_at'),
        ], $this->formatChanges($this->changes, $this->original));
    }

    /**
     * Get specific changes without format related to the event
     *
     * @return array
     */
    public function getEventName(): string
    {
        return 'SettingsUpdated';
    }
}
