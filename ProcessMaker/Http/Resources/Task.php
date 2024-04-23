<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Screen as ScreenResource;
use ProcessMaker\Http\Resources\ScreenVersion as ScreenVersionResource;
use ProcessMaker\Http\Resources\Users;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\ProcessTranslations\Languages;
use ProcessMaker\ProcessTranslations\ProcessTranslation;
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
        $dataManager = new DataManager();
        $array = parent::toArray($request);
        $include = explode(',', $request->input('include', ''));
        if (in_array('data', $include)) {
            $task = $this->resource->loadTokenInstance();
            $array['data'] = $dataManager->getData($task);
        }
        if (in_array('user', $include)) {
            $array['user'] = new Users($this->user);
        }
        if (in_array('requestor', $include)) {
            $array['requestor'] = new Users($this->processRequest->user);
        }
        if (in_array('processRequest', $include)) {
            $array['process_request'] = $this->processRequest->attributesToArray();
        }

        $parentProcessRequest = $this->processRequest->parentRequest;
        $array['can_view_parent_request'] = $parentProcessRequest && $request->user()->can('view', $parentProcessRequest);

        if (in_array('component', $include)) {
            $array['component'] = $this->getScreenVersion() ? $this->getScreenVersion()->parent->renderComponent() : null;
        }
        if (in_array('screen', $include)) {
            $screen = $this->getScreenVersion();
            if ($screen) {
                if ($screen->type === 'ADVANCED') {
                    $array['screen'] = $screen;
                } else {
                    $resource = new ScreenVersionResource($screen);
                    $array['screen'] = $resource->toArray($request);
                }
            } else {
                $array['screen'] = null;
            }

            if ($array['screen']) {
                // Apply translations to screen
                $process = Process::findOrFail($this->processRequest->process_id);
                $processTranslation = new ProcessTranslation($process);
                $array['screen']['config'] = $processTranslation->applyTranslations($array['screen']);
                $array['screen']['config'] = $this->removeInspectorMetadata($array['screen']['config']);

                // Apply translations to nested screens
                if (array_key_exists('nested', $array['screen'])) {
                    foreach ($array['screen']['nested'] as &$nestedScreen) {
                        $nestedScreen['config'] = $processTranslation->applyTranslations($nestedScreen);
                        $nestedScreen['config'] = $this->removeInspectorMetadata($nestedScreen['config']);
                    }
                }
            }
        }

        if (in_array('requestData', $include)) {
            $data = new StdClass();
            if ($this->processRequest->data) {
                $task = $this->resource->loadTokenInstance();
                $data = $dataManager->getData($task);
            }
            $array['request_data'] = $data;
        }
        if (in_array('loopContext', $include)) {
            $array['loop_context'] = $this->getLoopContext();
        }
        if (in_array('definition', $include)) {
            $array['definition'] = $this->getDefinition();
        }
        if (in_array('bpmnTagName', $include)) {
            $array['bpmn_tag_name'] = $this->getBpmnDefinition()->localName;
        }
        if (in_array('interstitial', $include)) {
            $interstitial = $this->getInterstitial();
            $array['allow_interstitial'] = $interstitial['allow_interstitial'];
            $array['interstitial_screen'] = $interstitial['interstitial_screen'];
            $array['interstitial_screen']['config'] = $this->removeInspectorMetadata($array['interstitial_screen']['config']);
        }
        if (in_array('userRequestPermission', $include)) {
            $array['user_request_permission'] = $this->loadUserRequestPermission($this->processRequest, Auth::user(), []);
        }
        /**
         * @deprecated since 4.1 Use instead `/api/1.0/users`
         */

        // Used to retrieve the assignable users for self service tasks
        $needToRecalculateAssignableUsers = !array_key_exists('assignable_users', $array)
                                                || count($array['assignable_users']) < 1;
        if (in_array('assignableUsers', $include) && $needToRecalculateAssignableUsers) {
            $nivel = array_search('request', array_column(debug_backtrace(), 'function'));
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

        return $array;
    }

    /**
     * Removes the inspector metadata from the screen configuration
     *
     * @param array $config
     * @return array
     */
    private function removeInspectorMetadata(array $config)
    {
        foreach($config as $i => $page) {
            $config[$i]['items'] = $this->removeInspectorMetadataItems($page['items']);
        }
        return $config;
    }

    /**
     * Removes the inspector metadata from the screen configuration items
     *
     * @param array $items
     * @return array
     */
    private function removeInspectorMetadataItems(array $items)
    {
        foreach($items as $i => $item) {
            if (isset($item['inspector'])) {
                unset($item['inspector']);
            }
            if (isset($item['component']) && $item['component'] === 'FormMultiColumn') {
                foreach($item['items'] as $c => $col) {
                    $item['items'][$c] = $this->removeInspectorMetadataItems($col);
                }
            } elseif (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->removeInspectorMetadataItems($item['items']);
            }
            $items[$i] = $item;
        }
        return $items;
    }

    private function loadUserRequestPermission(ProcessRequest $request, User $user = null, array $permissions)
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

    private function addUser($data, $user)
    {
        if (!$user) {
            return $data;
        }

        $userData = $user->attributesToArray();
        unset($userData['remember_token']);

        $data = array_merge($data, ['_user' => $userData]);
        if (!empty($this->token_properties['data'])) {
            $data = array_merge($data, $this->token_properties['data']);
        }

        return $data;
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
}
