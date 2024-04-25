<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use ProcessMaker\Models\ProcessMakerModel;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TaskDraft extends ProcessMakerModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = [
        'id',
        'updated_at',
        'created_at',
    ];

    protected $fillable = [
        'task_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function processRequestToken()
    {
        return $this->belongsTo(ProcessRequestToken::class, 'task_id');
    }

    /**
     * If any files were added or deleted to a task draft, we handle
     * them here, after the task has been submitted.
     *
     * @param ProcessRequestToken $task
     * @return void
     */
    public static function moveDraftFiles(ProcessRequestToken $task)
    {
        $draft = $task->draft;
        if (!$draft) {
            return;
        }

        self::handleDeletedFiles($draft);

        // Associate draft files with the actual process request
        foreach ($draft->getMedia() as $mediaItem) {
            $processRequestId = $mediaItem->getCustomProperty('parent_process_request_id');
            if ($processRequestId) {
                $processRequest = ProcessRequest::find($processRequestId);
                if ($processRequest) {
                    $existingFilesToDelete = self::filesToDelete($task, $mediaItem);
                    self::reAssociateMediaItem($mediaItem, $processRequest);
                    self::deleteFiles($existingFilesToDelete);
                }
            }
        }
    }

    private static function handleDeletedFiles($draft)
    {
        // Handle deleted files
        foreach (Arr::get($draft->data, '__deleted_files', []) as $fileId) {
            $file = Media::find($fileId);
            if ($file) {
                $file->delete();
            }
        }
    }

    private static function filesToDelete($task, $mediaItem)
    {
        // Keep track to previous files to delete
        $delete = [];
        foreach ($task->processRequest->getMedia() as $existingMediaItem) {
            $existingMediaDataName = $existingMediaItem->getCustomProperty('data_name');
            $mediaItemDataName = $mediaItem->getCustomProperty('data_name');
            if (
                $existingMediaDataName === $mediaItemDataName &&
                !$mediaItem->getCustomProperty('is_multiple')
            ) {
                $delete[] = $existingMediaItem;
            }
        }

        return $delete;
    }

    private static function reAssociateMediaItem($mediaItem, $processRequest)
    {
        $mediaItem->model_type = ProcessRequest::class;
        $mediaItem->model_id = $processRequest->id;
        $mediaItem->forgetCustomProperty('parent_process_request_id');
        $mediaItem->saveOrFail();
    }

    private static function deleteFiles($delete)
    {
        // Delete previous files
        foreach ($delete as $existingMediaItem) {
            $existingMediaItem->delete();
        }
    }
}
