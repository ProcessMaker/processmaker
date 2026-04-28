<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\ClientRepository;
use ProcessMaker\Events\AuthClientCreated;
use ProcessMaker\Events\AuthClientDeleted;
use ProcessMaker\Events\AuthClientUpdated;
use ProcessMaker\Http\Resources\AuthClient as AuthClientResource;

class ClientController
{
    public function __construct(
        protected ClientRepository $clients,
        protected \Illuminate\Contracts\Validation\Factory $validation,
    ) {
    }

    public function index(Request $request)
    {
        $clients = \Laravel\Passport\Client::where('revoked', false)->get();

        return AuthClientResource::collection($clients);
    }

    public function show(Request $request, $clientId)
    {
        $client = $this->clients->findForUser($clientId, $request->user());

        if (!$client) {
            return new Response('', 404);
        }

        return new AuthClientResource($client);
    }

    public function store(Request $request)
    {
        $this->validate($request);

        $personalAccess = in_array('personal_access_client', $request->types);
        $password = in_array('password_client', $request->types);
        $redirect = in_array('authorization_code_grant', $request->types) ? $request->redirect : '';

        // Use ClientRepository methods based on type
        if ($personalAccess) {
            $client = $this->clients->createPersonalAccessGrantClient(
                $request->name
            );
        } elseif ($password) {
            $client = $this->clients->createPasswordGrantClient(
                $request->name,
                null, // provider
                true // confidential
            );
        } else {
            // Authorization code grant
            $client = $this->clients->createAuthorizationCodeGrantClient(
                $request->name,
                $redirect ? explode(',', $redirect) : [],
                true, // confidential
                $request->user()
            );
        }

        $client->makeVisible('secret');
        AuthClientCreated::dispatch($client->getAttributes());

        return new AuthClientResource($client);
    }

    public function update(Request $request, $clientId)
    {
        $client = $this->clients->findForUser($clientId, $request->user());

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
        ]);

        $original = array_intersect_key($client->getOriginal(), $client->getDirty());
        $client->save();

        AuthClientUpdated::dispatch($clientId, $original, $client->getChanges(), $request->name);

        return new AuthClientResource($client);
    }

    public function destroy(Request $request, $clientId)
    {
        $client = $this->clients->findForUser($clientId, $request->user());

        if (!$client) {
            return new Response('', 404);
        }

        $attributes = $client->getAttributes();
        $this->clients->delete($client);
        AuthClientDeleted::dispatch($attributes);

        return new Response('', 204);
    }

    private function validate($request)
    {
        $rules = [
            'name'     => 'required|max:255',
            'types'    => 'array|min:1|required',
            'types.*'  => 'in:authorization_code_grant,password_client,personal_access_client',
        ];

        if (is_array($request->types) && in_array('authorization_code_grant', $request->types)) {
            $rules['redirect'] = 'required|url|max:2000';
        }

        $this->validation->make($request->all(), $rules, [
            'min' => __('The Auth-Client must have at least :min item chosen.'),
        ])->validate();
    }
}
