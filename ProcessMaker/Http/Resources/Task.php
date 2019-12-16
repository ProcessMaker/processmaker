<?php

namespace ProcessMaker\Http\Resources;

use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Users;
use ProcessMaker\Models\User;
use StdClass;

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
        if (in_array('data', $include)) {
            $array['data'] = $this->processRequest->data;
        }
        if (in_array('user', $include)) {
            $array['user'] = new Users($this->user);
        }
        if (in_array('requestor', $include)) {
            $array['requestor'] = new Users($this->processRequest->user);
        }
        if (in_array('processRequest', $include)) {
            $array['process_request'] = new Users($this->processRequest);
        }
        if (in_array('component', $include)) {
            $array['component'] = $this->getScreen() ? $this->getScreen()->renderComponent() : null;
        }
        if (in_array('screen', $include)) {
            $array['screen'] = $this->getScreen()->toArray();
        }
        if (in_array('requestData', $include)) {
            $array['request_data'] = $this->processRequest->data ?: new StdClass();
        }
        if (in_array('definition', $include)) {
            $array['definition'] = $this->getDefinition();
        }
        if (in_array('assignableUsers', $include)) {
            $definition = $this->getDefinition();
            $assignment = isset($definition['assignment']) ? $definition['assignment'] : 'requester';
            switch ($assignment) {
                case 'self_service':
                case 'cyclical':
                case 'group':
                    $ids = $this->process->getAssignableUsers($this->element_id);
                    $users = User::where('status', 'ACTIVE')->whereIn('id', $ids)->get();
                    break;
                case 'user':
                case 'requester':
                    $users = User::where('status', 'ACTIVE')->get();
                    break;
                default:
                    $users = [];
            }
            $array['assignable_users'] = $users;
        }
        return $array;
    }
}
