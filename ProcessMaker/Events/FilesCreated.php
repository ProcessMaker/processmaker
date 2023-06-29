<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Media;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class FilesCreated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $media;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $fileId)
    {
        $this->media = Media::find(['id' => $fileId])->toArray();
        $this->media = head($this->media);
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'file_name' => [
                'label' => $this->media['name'],
                'link' => route('file-manager.index', ['public/' . $this->media['file_name']]),
            ],
            'created_at' => $this->media['created_at'],
        ];
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [
            'id' => $this->media['id']
        ];
    }

    /**
     * Get the Event name
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'FilesCreated';
    }
}
