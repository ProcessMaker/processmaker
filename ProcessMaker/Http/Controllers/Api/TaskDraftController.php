<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;

class TaskDraftController extends Controller
{
    public function update(Request $request, ProcessRequestToken $task)
    {
        $search = ['task_id' => $task->id];
        $draft = TaskDraft::firstOrNew($search, ['data' => []]);
        // Do not overwrite __deleted_files
        $deletedFiles = Arr::get($draft->data, '__deleted_files');
        $data = $request->all();
        if ($deletedFiles) {
            $data['__deleted_files'] = $deletedFiles;
        }
        $draft->data = $data;
        $draft->saveOrFail();

        return new ApiResource($draft);
    }

    public function delete(ProcessRequestToken $task)
    {
        // Use get()->each to fire the delete event for each draft
        TaskDraft::where('task_id', $task->id)->get()->each->delete();

        return response([], 204);
    }
}
