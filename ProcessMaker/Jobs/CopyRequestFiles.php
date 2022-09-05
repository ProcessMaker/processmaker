<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\ProcessRequest;

class CopyRequestFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $from;

    protected $to;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ProcessRequest $from, ProcessRequest $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * Copy files from one process to another, overwriting any with the same data_name
     *
     * @return void
     */
    public function handle()
    {
        $media = Media::where('model_id', $this->from->id);
        foreach ($media as $fileToCopy) {
            $originalCreatedBy = $fileToCopy->getCustomProperty('createdBy');
            foreach ($this->to->getMedia() as $mediaItem) {
                if ($mediaItem->getCustomProperty('data_name') == $fileToCopy->getCustomProperty('data_name') &&
                    $mediaItem->getCustomProperty('parent') == $fileToCopy->getCustomProperty('parent')) {
                    $originalCreatedBy = $mediaItem->getCustomProperty('createdBy');
                    $mediaItem->delete();
                }
            }

            $newFile = $fileToCopy->copy($this->to);
            $newFile->setCustomProperty('createdBy', $originalCreatedBy);
            $newFile->save();

            //update the file with new ID
            $processRequest = ProcessRequest::find($newFile->model_id);
            $data = $processRequest->data;
            Arr::set($data, $fileToCopy->getCustomProperty('data_name'), $newFile->id);
            $processRequest->data = $data;
            $this->to->getDataStore()->putData($fileToCopy->getCustomProperty('data_name'), $newFile->id);
            $processRequest->save();
        }
    }
}
