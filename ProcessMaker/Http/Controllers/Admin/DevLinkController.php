<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
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

        return view('admin.devlink.index');
    }

    public function getOauthClient(Request $request)
    {
        $devLinkId = $request->input('devlink_id');
        $redirectUrl = $request->input('redirect_url');

        $client = Client::where([
            'name' => 'devlink',
            'redirect' => $redirectUrl,
        ])->first();

        if (!$client) {
            $clientRepository = app('Laravel\Passport\ClientRepository');
            $client = $clientRepository->create(null, 'devlink', $redirectUrl);
        }

        $query = http_build_query([
            'devlink_id' => $devLinkId,
            'client_id' => $client->id,
            'client_secret' => $client->secret,
        ]);

        return redirect($redirectUrl . '?' . $query);
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
}
