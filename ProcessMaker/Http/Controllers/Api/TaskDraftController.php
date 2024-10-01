<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\SanitizeHelper;
use ProcessMaker\Models\TaskDraft;

class TaskDraftController extends Controller
{
    public function index(Request $request, ProcessRequestToken $task)
    {
        $search = ['task_id' => $task->id];
        $draft = TaskDraft::where($search)->first();

        if ($draft) {
            $draftData = $draft->data;
            return new ApiResource($draftData);
        }

        return new ApiResource(null);
    }

    public function update(Request $request, ProcessRequestToken $task)
    {
        $search = ['task_id' => $task->id];
        $draft = TaskDraft::firstOrNew($search, ['data' => []]);
        // Do not overwrite __deleted_files
        $deletedFiles = Arr::get($draft->data, '__deleted_files');
        $data = json_decode($request->getContent(), true);
        $data = SanitizeHelper::sanitizeData( $data, null, $task->processRequest->do_not_sanitize ?? []);
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

        $requestFiles = $task->processRequest->requestFiles();

        return response(['request_files' => $requestFiles], 200);
    }
}
