<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class FilesDownloaded implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    public const NAME_PUBLIC_FILES = 'Public Files';

    private string $fileName = '';
    private string $processName = '';

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $file = '', ProcessRequest $data = null)
    {
        $this->fileName = $file;

        // Check if the file is related to the request
        if (!is_null($data)) {
            // Get the process name
            if (static::NAME_PUBLIC_FILES !== $data->getAttribute('name')) {
                $this->processName = $data->getAttribute('name');
            }
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
                'label' => $this->fileName,
                'link' => route('file-manager.index', ['public/'. $this->fileName])
            ],
            'process' => $this->processName,
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
