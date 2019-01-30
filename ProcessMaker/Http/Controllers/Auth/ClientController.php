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
        $this->validation->make($request->all(), [
            'name' => 'required|max:255',
            'redirect' => 'required|url|max:10',
        ])->validate();

        return $this->clients->create(
            $request->user()->getKey(), $request->name, $request->redirect
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

        $this->validation->make($request->all(), [
            'name' => 'required|max:255',
            'redirect' => 'required|url|max:10',
        ])->validate();

        return $this->clients->update(
            $client, $request->name, $request->redirect
        );
    }
}
