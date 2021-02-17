<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;

class DataManager
{
    private $reservedVariables = [
        '_user',
        '_request',
        '_parent',
        'loopCounter',
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
     *
     * @return array
     */
    public function getData($token)
    {
        if ($token->isMultiInstance()) {
            $data = $token->getProperty('data', $token->token_properties['data']) ?: [];
        } else {
            $data = $token->processRequest->data ?: [];
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
            $data['_parent'] = $this->getRequestData($token->processRequest);
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
    public function getRequestData(ProcessRequest $request)
    {
        $data = $request->data ?: [];
        foreach ($this->hiddenVariables as $key) {
            unset($data[$key]);
        }
        return $data;
    }
}
