<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\ProcessRequestToken;
use Illuminate\Support\Arr;

class TaskDraftController extends Controller
{
    public function update(Request $request, ProcessRequestToken $task)
    {
        $search = ['task_id' => $task->id];
        $draft = TaskDraft::updateOrCreate($search,['data' => $request->all()]);

        return new ApiResource($draft);
    }
    public function delete(ProcessRequestToken $task)
    {
        TaskDraft::where('task_id', $task->id)->delete();
        return response([], 204);
    }
}
