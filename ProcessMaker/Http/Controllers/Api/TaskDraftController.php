<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\TaskDraft;

class TaskDraftController extends Controller
{
    public function update(Request $request, ProcessRequestToken $task)
    {
        $search = ['task_id' => $task->id];

        $draft = TaskDraft::updateOrCreate($search, $request);

        return new ApiResource($draft);
    }
}
