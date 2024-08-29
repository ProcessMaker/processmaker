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
        $this->storeClientCredentials($request);

        return view('admin.devlink.index');
    }

    public function getOauthClient(Request $request)
    {
        $devLinkId = $request->input('devlink_id');
        $redirectUrl = $request->input('redirect_url');

        // Get the client with the name "devlink" or create it if it doesn't exist
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
            $devlink->url = $request->input('url');
            $devlink->client_id = $request->input('client_id');
            $devlink->client_secret = $request->input('client_secret');
            $devlink->access_token = $request->input('access_token');
            $devlink->refresh_token = $request->input('refresh_token');
            $devlink->expires_in = $request->input('expires_in');
            $devlink->save();
        }
    }
}
