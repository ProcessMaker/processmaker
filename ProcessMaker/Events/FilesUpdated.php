<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Media;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class FilesUpdated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private Media $media;

    private array $changes;

    private array $original;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Media $data, array $changes, array $original)
    {
        $this->media = $data;
        $this->changes = $changes;
        $this->original = $original;
        unset($this->changes['name']);
        unset($this->original['name']);
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return array_merge([
            'name' => [
                'label' => $this->media->getAttribute('name'),
                'id' => $this->media->getAttribute('id'),
            ],
            'last_modified' => $this->media->getAttribute('updated_at'),
        ], $this->formatChanges($this->changes, $this->original));
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->media->getAttribute('id'),
        ];
    }

    /**
     * Get the Event name
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'FilesUpdated';
    }
}
