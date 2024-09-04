<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class FilesDownloaded implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $name = [];

    private string $processName = '';

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Media $media, ProcessRequest $data = null)
    {
        // Check if the file is related to the request
        if (!is_null($data)) {
            $this->processName = $data->getAttribute('name');
            // Link to the request
            $this->name = [
                'label' => $data->getAttribute('id'),
                'link' => route('requests.show', $data),
                'id' => $media['id'],
            ];
        } else {
            // Link to file in the package
            $this->name = [
                'label' => $media['name'],
                'link' => route('file-manager.index', ['public/' . $media['name']]),
                'id' => $media['id'],
            ];
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
            'name' => $this->name,
            'process' => $this->processName,
            'accessed_at' => Carbon::now(),
        ];
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getChanges(): array
    {
        return [];
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
