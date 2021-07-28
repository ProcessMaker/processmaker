<?php

namespace ProcessMaker\Http\Resources;

use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;

class ScreenVersion extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $screenVersion = parent::toArray($request);

        $include = explode(',', $request->input('include', ''));

        if (in_array('nested', $include)) {
            
            $taskId = $request->route('task')->id;
            $processRequest = null;
            if ($taskId) {
                $task = ProcessRequestToken::findOrFail($taskId);
                $processRequest = $task->processRequest;
            }

            $nested = [];
            foreach ($this->parent->nestedScreenIds($processRequest) as $id) {
                $nestedScreen = Screen::findOrFail($id);
                $nested[] = $nestedScreen->versionFor($processRequest)->toArray();
            }
            $screenVersion['nested'] = $nested;
        }
        return $screenVersion;
    }
}
