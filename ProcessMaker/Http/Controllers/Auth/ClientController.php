<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ClientController as PassportClientController;
use ProcessMaker\Http\Resources\AuthClient as AuthClientResource;

class ClientController extends PassportClientController
{
     /**
     * List auth clients
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function index(Request $request)
    {
        $clients = \Laravel\Passport\Client::where('revoked', false)->get();
        return AuthClientResource::collection($clients);
    }
     
    /**
     * Get an individual auth client 
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $clientId
     * @return array
     */
    public function show(Request $request, $clientId)
    {
        // $client = $this->clients->find($clientId);
        $client = parent::show($request, $clientId);
        return new AuthClientResource($client);
    }

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

        $client = $this->clients->create(
            $request->user()->getKey(), $request->name, $redirect, $personalAccess, $password
        )->makeVisible('secret');

        return new AuthClientResource($client);
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
        
        $client = $this->clients->find($clientId);

        if (!$client) {
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

        return new AuthClientResource($client);
    }

    /**
     * Delete the given client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $clientId
     * @return null
     */
    public function destroy(Request $request, $clientId)
    {
        $client = $this->clients->find($clientId);

        if (!$client) {
            return new Response('', 404);
        }
        
        $this->clients->delete($client);
        return response('', 204);
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
