<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Media;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class FilesDownloaded implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private Media $media;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Media $data)
    {
        $this->media = $data;
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
                'label' => $this->media['name'],
                'link' => route('file-manager.index', ['public/'. $this->media['file_name']])
            ],
            'accessed_at' => Carbon::now()
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
            'id' => $this->media['id'] ?? ''
        ];
    }

    /**
     * Get the Event name
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'FilesDownloaded';
    }
}
