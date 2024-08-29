<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\DevLink;

class DevLinkController extends Controller
{
    public function index()
    {
        return DevLink::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'url' => ['required', 'url'],
        ]);

        $devLink = new DevLink();
        $devLink->name = $request->input('name');
        $devLink->url = $request->input('url');
        $devLink->saveOrFail();

        return $devLink;
    }

    public function update(Request $request, string $id)
    {
    }

    public function destroy(string $id)
    {
    }

    public function ping(Request $request, DevLink $devLink)
    {
        return $devLink->client()->get(route('api.devlink.pong', [], false));
    }

    public function pong()
    {
        return ['status' => 'ok'];
    }
}
