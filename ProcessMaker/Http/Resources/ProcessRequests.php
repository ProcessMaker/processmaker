<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProcessRequests extends ApiResource
{
    public function toArray($request)
    {
        $array = parent::toArray($request);
        $array['process_version_alternative'] = $this->processVersionAlternative;
        $include = explode(',', $request->input('include', ''));

        if (in_array('data', $include)) {
            $array['data'] = $this->filterMagicVariables($this->data);
        }
        if (in_array('summary', $include)) {
            $array['summary'] = $this->summary();
        }
        if (in_array('participants', $include)) {
            $array['participants'] = $this->participants;
        }
        if (in_array('activeTasks', $include)) {
            $array['active_tasks'] = $this->tokens()
                ->select(['id', 'element_name', 'status', 'user_id'])
                ->where('status', 'ACTIVE')
                ->where('element_type', 'task')
                ->get();
        }
        if (in_array('user', $include)) {
            $array['user'] = new Users($this->user);
        }

        return $array;
    }

    private function filterMagicVariables($data)
    {
        foreach ($data as $key => $datum) {
            if (stripos($key, '_') === 0) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
