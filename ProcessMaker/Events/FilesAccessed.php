<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class FilesAccessed implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private array $linkFile = [];

    private string $processName = '';

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $name, ProcessRequest $data = null, Media $media = null)
    {
        // Check if the file is related to the request
        if (!is_null($data)) {
            $this->processName = $data->getAttribute('name');
            // Link to the request
            $this->linkFile = [
                'label' => $data->getAttribute('id'),
                'link' => route('requests.show', $data),
            ];
        } else {
            // Link to file in the package
            $this->linkFile = [
                'label' => $name,
                'link' => route('file-manager.index', ['public/' . $name]),
                'id' => $media->id,
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
            'name' => $this->linkFile,
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
        return 'FilesAccessed';
    }
}
