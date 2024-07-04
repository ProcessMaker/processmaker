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
        'process',
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
            $config = json_decode($definition['config'], true) ?: [];
            $isSelfService = $config['selfService'] ?? false;
            if ($isSelfService) {
                $users = [];
                $selfServiceUsers = $array['self_service_groups']['users'];
                $selfServiceGroups = $array['self_service_groups']['groups'];

                if ($selfServiceUsers !== ['']) {
                    $users = $this->addActiveAssignedUsers($selfServiceUsers, $users);
                }

                if ($selfServiceGroups !== ['']) {
                    $users = $this->addActiveAssignedGroupMembers($selfServiceGroups, $users);
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

    /**
     * Add the active users to the list of assigned users
     *
     * @param array $users List of users ids
     * @param array $assignedUsers List of assigned users
     *
     * @return array List of assigned users with additional active users
     */
    private function addActiveAssignedUsers(array $users, array $assignedUsers)
    {
        $users = array_unique($users);
        $userChunks = array_chunk($users, 1000);
        foreach ($userChunks as $chunk) {
            $activeUsers = User::select('id')
                ->whereNotIn('status', Process::NOT_ASSIGNABLE_USER_STATUS)
                ->whereIn('id', $chunk)
                ->pluck('id')->toArray();
            $assignedUsers = array_merge($assignedUsers,$activeUsers);
        }

        return $assignedUsers;
    }

    /**
     * Add the active group members to the list of assigned users
     *
     * @param array $groups List of group ids
     * @param array $assignedUsers List of assigned users
     * @return array List of assigned users with additional active users
     */
    private function addActiveAssignedGroupMembers(array $groups, array $assignedUsers)
    {
        return (new Process)->getConsolidatedUsers($groups, $assignedUsers);
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
