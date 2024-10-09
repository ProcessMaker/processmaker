<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class FilesDeleted implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    private int $fileId;

    private string $fileName;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $id, string $name)
    {
        $this->fileId = $id;
        $this->fileName = $name;
    }

    /**
     * Get specific data related to the event
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'file_name' => $this->fileName,
            'deleted_at' => Carbon::now(),
            'name' => [
                'id' => $this->fileId,
            ],
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
            'id' => $this->fileId,
        ];
    }

    /**
     * Get the Event name
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'FilesDeleted';
    }
}
