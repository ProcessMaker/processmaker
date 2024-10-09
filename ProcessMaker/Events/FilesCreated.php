<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class FilesCreated implements SecurityLogEventInterface
{
    use Dispatchable;
    use FormatSecurityLogChanges;

    public const NAME_PUBLIC_FILES = 'Public Files';

    private array $media;

    private array $name = [];

    private string $processName = '';

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $fileId, ProcessRequest $data)
    {
        $this->media = Media::find(['id' => $fileId])->toArray();
        $this->media = head($this->media);

        // Check if the request is related to the package files
        if (static::NAME_PUBLIC_FILES === $data->getAttribute('name')) {
            $this->processName = '';
            // Link to file in the package
            $this->name = [
                'label' => $this->media['name'],
                'link' => route('file-manager.index', ['public/' . $this->media['file_name']]),
                'id' => $fileId,
            ];
        } else {
            $this->processName = $data->getAttribute('name');
            // Link to the request
            $this->name = [
                'label' => $data->getAttribute('id'),
                'link' => route('requests.show', $data),
                'id' => $fileId,
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
            'id' => $this->media['id'],
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
