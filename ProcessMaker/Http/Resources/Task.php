<?php

namespace ProcessMaker\Http\Resources;

use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Users;
use ProcessMaker\Models\User;
use ProcessMaker\Http\Resources\Screen as ScreenResource;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\ProcessRequestToken;
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
            $array['process_request'] = new Users($this->processRequest);
        }
        if (in_array('component', $include)) {
            $array['component'] = $this->getScreen() ? $this->getScreen()->renderComponent() : null;
        }
        if (in_array('screen', $include)) {
            $screen = $this->getScreen();
            if ($screen) {
                if ($screen->type === 'ADVANCED') {
                    $array['screen'] = $screen;
                } else {
                    $resource = new ScreenResource($screen);
                    $array['screen'] = $resource->toArray($request);
                }
            } else {
                $array['screen'] = null;
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
        }
        /**
         * @deprecated since 4.1 Use instead `/api/1.0/users`
         */
        if (in_array('assignableUsers', $include)) {
            $currentUser = \Auth::user();
            $users = User::where('status', 'ACTIVE')
                ->where('id', '!=', $currentUser->id)
                ->where('is_system', 'false')
                ->limit(100)
                ->get();
            $array['assignable_users'] = $users;
        }
        return $array;
    }

    private function addUser($data, $user)
    {
        if (!$user) {
            return $data;
        }

        $userData = $user->attributesToArray();
        unset($userData['remember_token']);

        $data =  array_merge($data, ['_user' => $userData]);
        if (!empty($this->token_properties['data'])) {
            $data =  array_merge($data, $this->token_properties['data']);
        }
        return $data;
    }
}
