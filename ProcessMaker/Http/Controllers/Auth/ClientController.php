<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ClientController as PassportClientController;

class ClientController extends PassportClientController
{

    /**
     * Store a new client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Laravel\Passport\Client
     */
    public function store(Request $request)
    {
        $this->validate($request);

        $personalAccess = in_array('personal_access_client', $request->types);
        $password = in_array('password_client', $request->types);
        $redirect = in_array('authorization_code_grant', $request->types) ? $request->redirect : '';

        return $this->clients->create(
            $request->user()->getKey(), $request->name, $redirect, $personalAccess, $password
        )->makeVisible('secret');
    }

    /**
     * Update the given client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $clientId
     * @return \Illuminate\Http\Response|\Laravel\Passport\Client
     */
    public function update(Request $request, $clientId)
    {
        
        $client = $this->clients->findForUser($clientId, $request->user()->getKey());

        if (! $client) {
            return new Response('', 404);
        }
        
        $this->validate($request);

        $personalAccess = in_array('personal_access_client', $request->types);
        $password = in_array('password_client', $request->types);
        $redirect = in_array('authorization_code_grant', $request->types) ? $request->redirect : '';

        $client->forceFill([
            'name' => $request->name,
            'redirect' => $redirect,
            'personal_access_client' => $personalAccess,
            'password_client' => $password,
        ])->save();

        return $client;
    }
    
    private function validate($request)
    {
        $rules = [
            'name'     => 'required|max:255',
            'types'    => 'array|min:1|required',
            'types.*'  => 'in:authorization_code_grant,password_client,personal_access_client'
        ];
        if (is_array($request->types) && in_array('authorization_code_grant', $request->types)) {
            $rules['redirect'] = 'required|url|max:2000';
        }
        $this->validation->make($request->all(), $rules)->validate();
    }
}
