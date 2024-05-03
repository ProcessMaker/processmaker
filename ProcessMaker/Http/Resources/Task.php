<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Support\Arr;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Screen as ScreenResource;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\TaskResourceIncludes;

class Task extends ApiResource
{
    use TaskResourceIncludes;

    private $loadedData = null;

    /**
     * A list of includes that have methods `include{Name}`
     * in the TaskResourceIncludes trait.
     */
    private $includeMethods = [
        'data',
        'user',
        'requestor',
        'processRequest',
        'draft',
        'component',
        'screen',
        'requestData',
        'loopContext',
        'definition',
        'bpmnTagName',
        'interstitial',
        'userRequestPermission',
    ];

    private $process = null;

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

        $this->process = Process::findOrFail($this->processRequest->process_id);

        foreach ($this->includeMethods as $key) {
            if (!in_array($key, $include)) {
                continue;
            }
            $method = 'include' . ucfirst($key);
            $result = $this->$method($request);
            if (count($result) === 1) {
                $resultKey = array_key_first($result);
                $array[$resultKey] = $result[$resultKey];
            } else {
                $array = array_merge($array, $result);
            }
        }

        $this->addCanViewParentRequest($array, $request);

        $this->addAssignableUsers($array, $include);

        return $array;
    }

    private function addCanViewParentRequest(&$array, $request)
    {
        $parentProcessRequest = $this->processRequest->parentRequest;
        $array['can_view_parent_request'] =
            $parentProcessRequest && $request->user()->can('view', $parentProcessRequest);
    }

    private function addAssignableUsers(&$array, $include)
    {
        /**
         * @deprecated since 4.1 Use instead `/api/1.0/users`
         */

        // Used to retrieve the assignable users for self service tasks
        $needToRecalculateAssignableUsers = !array_key_exists('assignable_users', $array)
                                                || count($array['assignable_users']) < 1;
        if (in_array('assignableUsers', $include) && $needToRecalculateAssignableUsers) {
            $definition = $this->getDefinition();
            if (isset($definition['assignment']) && $definition['assignment'] == 'self_service') {
                $users = [];
                $selfServiceUsers = $array['self_service_groups']['users'];
                $selfServiceGroups = $array['self_service_groups']['groups'];

                if ($selfServiceUsers !== ['']) {
                    $assignedUsers = $this->getAssignedUsers($selfServiceUsers);
                    $users = array_unique(array_merge($users, $assignedUsers));
                }

                if ($selfServiceGroups !== ['']) {
                    $assignedUsers = $this->getAssignedGroupMembers($selfServiceGroups);
                    $users = array_unique(array_merge($users, $assignedUsers));
                }
                $array['assignable_users'] = $users;
            }
        }
    }

    private function loadUserRequestPermission(ProcessRequest $request, User $user, array $permissions)
    {
        $permissions[] = [
            'process_request_id' => $request->id,
            'allowed' => $user ? $user->can('view', $request) : false,
        ];

        if ($request->parentRequest && $user) {
            $permissions = $this->loadUserRequestPermission($request->parentRequest, $user, $permissions);
        }

        return $permissions;
    }

    private function getAssignedUsers($users)
    {
        foreach ($users as $user) {
            $assignedUsers[] = User::where('status', 'ACTIVE')->where('id', $user)->first();
        }

        return $assignedUsers;
    }

    private function getAssignedGroupMembers($groups)
    {
        \Log::debug('groups', ['groups' =>$groups]);
        foreach ($groups as $group) {
            $groupMembers = GroupMember::where('group_id', $group)->get();
            foreach ($groupMembers as $member) {
                $assignedUsers[] = User::where('status', 'ACTIVE')->where('id', $member->member_id)->first();
            }
        }

        return $assignedUsers;
    }

    private function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }
        $dataManager = new DataManager();
        $task = $this->resource->loadTokenInstance();
        $this->loadedData = $dataManager->getData($task);

        return $this->loadedData;
    }
}
