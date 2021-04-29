<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;

class DataManager
{
    private $reservedVariables = [
        '_user',
        '_request',
        '_parent',
        'loopCounter',
        'numberOfActiveInstances',
        'numberOfInstances',
        'numberOfCompletedInstances',
        'numberOfTerminatedInstances',
        'loopCharacteristics',
    ];

    private $hiddenVariables = [
        'loopCharacteristics',
    ];

    /**
     * Update data through a $token
     *
     * @param ProcessRequestToken $token
     * @param array $data
     *
     * @return void
     */
    public function updateData(ProcessRequestToken $token, array $data)
    {
        if ($token->isMultiInstance()) {
            $tokenData = $token->getProperty('data');
            foreach ($data as $key => $value) {
                if (in_array($key, $this->reservedVariables)) {
                    continue;
                }
                $tokenData[$key] = $value;
            }
            $token->setProperty('data', $tokenData);
        } else {
            $dataStore = $token->getInstance()->getDataStore();
            foreach ($data as $key => $value) {
                if (in_array($key, $this->reservedVariables)) {
                    continue;
                }
                $dataStore->putData($key, $value);
            }
        }
    }

    /**
     * Get data for the $token
     *
     * @param ProcessRequestToken $token
     * @param bool $whenTokenSaved If true returns the Request Data as when the Token was saved
     *
     * @return array
     */
    public function getData(ProcessRequestToken $token, bool $whenTokenSaved = false)
    {
        if ($token->isMultiInstance()) {
            $data = $token->getProperty('data', ($token->token_properties ?? [])['data'] ?? []) ?: [];
            $data = $this->addLoopInstanceProperties($data, $token->getDefinition(true), $token);
        } else {
            if ($whenTokenSaved) {
                $data = $token->data ?: [];
            } else {
                $data = $token->processRequest->data ?: [];
            }
        }

        // Magic Variable: _user
        $user = $token->user ?: Auth::user();
        if ($user) {
            $userData = $user->attributesToArray();
            unset($userData['remember_token']);
            $data['_user'] = $userData;
        }

        // Magic Variable: _request
        $data['_request'] = $token->processRequest->attributesToArray();

        // Magic Variable: _parent
        if ($token->isMultiInstance()) {
            if ($whenTokenSaved) {
                $data['_parent'] = $token->data ?: [];
            } else {
                $data['_parent'] = $this->getRequestData($token->processRequest, ['_user', '_request', '_parent']);
            }
        }

        foreach ($this->hiddenVariables as $key) {
            unset($data[$key]);
        }
        return $data;
    }

    /**
     * Get root data from request
     *
     * @return array
     */
    public function getRequestData(ProcessRequest $request, $hidden = [])
    {
        $hidden = \array_merge($this->hiddenVariables, $hidden);
        $data = $request->data ?: [];
        foreach ($hidden as $key) {
            unset($data[$key]);
        }
        return $data;
    }

    private function addLoopInstanceProperties(array $data, ActivityInterface $activity, ProcessRequestToken $token)
    {
        $data['numberOfInstances'] = $activity->getLoopCharacteristics()->getLoopInstanceProperty($token, 'numberOfInstances', 0);
        $data['numberOfActiveInstances'] = $activity->getLoopCharacteristics()->getLoopInstanceProperty($token, 'numberOfActiveInstances', 0);
        $data['numberOfCompletedInstances'] = $activity->getLoopCharacteristics()->getLoopInstanceProperty($token, 'numberOfCompletedInstances', 0);
        return $data;
    }
}
