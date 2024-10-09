<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Client;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\DevLink;

class DevLinkController extends Controller
{
    public function index(Request $request)
    {
        $updatedDevLink = $this->storeClientCredentials($request);

        if ($updatedDevLink) {
            return redirect($updatedDevLink->getOauthRedirectUrl());
        }

        $updatedDevLink = $this->storeOauthCredentials($request);
        if ($updatedDevLink) {
            return redirect(route('devlink.index'));
        }

        return view('admin.devlink.index');
    }

    public function getOauthClient(Request $request)
    {
        $request->validate([
            'devlink_id' => 'required',
            'redirect_uri' => ['required', 'url'],
        ]);

        $devLinkId = $request->input('devlink_id');
        $redirectUri = $request->input('redirect_uri');

        $client = Client::where([
            'name' => 'devlink',
            'redirect' => $redirectUri,
        ])->first();

        if (!$client) {
            $clientRepository = app('Laravel\Passport\ClientRepository');
            $client = $clientRepository->create(null, 'devlink', $redirectUri);
        }

        $query = http_build_query([
            'devlink_id' => $devLinkId,
            'client_id' => $client->id,
            'client_secret' => $client->secret,
        ]);

        return redirect($redirectUri . '?' . $query);
    }

    private function storeClientCredentials(Request $request)
    {
        if (
            $request->has('devlink_id') &&
            $request->has('client_id') &&
            $request->has('client_secret')
        ) {
            $devlink = DevLink::findOrFail($request->input('devlink_id'));
            $devlink->update([
                'client_id' => $request->input('client_id'),
                'client_secret' => $request->input('client_secret'),
            ]);

            return $devlink;
        }

        return false;
    }

    private function storeOauthCredentials(Request $request)
    {
        if (
            $request->has('state') &&
            $request->has('code')
        ) {
            $devlink = DevLink::where('state', $request->input('state'))->firstOrFail();

            $response = Http::asForm()->post($devlink->url . '/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $devlink->client_id,
                'client_secret' => $devlink->client_secret,
                'redirect_uri' => route('devlink.index'),
                'code' => $request->input('code'),
            ]);

            $response = $response->json();
            $devlink->update([
                'access_token' => $response['access_token'],
                'refresh_token' => $response['refresh_token'],
                'expires_in' => $response['expires_in'],
            ]);

            return $devlink;
        }

        return false;
    }
}
