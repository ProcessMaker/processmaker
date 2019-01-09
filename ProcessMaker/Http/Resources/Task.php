<?php

namespace ProcessMaker\Http\Resources;

use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Users;
use ProcessMaker\Models\User;

class Task extends ApiResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $array = parent::toArray($request);
        $include = explode(',', $request->input('include', ''));
        if (in_array('user', $include)) {
            $array['user'] = new Users($this->user);
        }
        if (in_array('definition', $include)) {
            $array['definition'] = $this->getDefinition();
        }
        if (in_array('assignableUsers', $include)) {
            $definition = $this->getDefinition();
            $assignment = isset($definition['assignment']) ? $definition['assignment'] : 'requestor';
            switch ($assignment) {
                case 'cyclical':
                case 'group':
                    $ids = $this->process->getAssignableUsers($this->element_id);
                    $users = User::where('status', 'ACTIVE')->whereIn('id', $ids)->get();
                    break;
                case 'user':
                case 'requestor':
                    $users = User::where('status', 'ACTIVE')->get();
                    break;
                default:
                    $users = [];
            }
            $array['assignableUsers'] = $users;
        }
        return $array;
    }
}
