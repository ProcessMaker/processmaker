<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Bundle;
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
            'name' => ['required', 'unique:dev_links,name'],
            'url' => ['required', 'url', 'unique:dev_links,url'],
        ]);

        $devLink = new DevLink();
        $devLink->name = $request->input('name');
        $devLink->url = $request->input('url');
        $devLink->saveOrFail();

        return [
            ...$devLink->toArray(),
            'redirect_uri' => route('devlink.index'),
        ];
    }

    public function update(Request $request, DevLink $devLink)
    {
        $devLink->name = $request->input('name');
        $devLink->saveOrFail();

        return $devLink;
    }

    public function destroy(DevLink $devLink)
    {
        $devLink->delete();
    }

    public function ping(DevLink $devLink)
    {
        return $devLink->client()->get(route('api.devlink.pong', [], false));
    }

    public function pong()
    {
        return ['status' => 'ok'];
    }

    public function localBundles(Request $request)
    {
        if ($request->has('published')) {
            return Bundle::published()->get();
        }

        return Bundle::get();
    }

    public function remoteBundles(DevLink $devLink)
    {
        return $devLink->remoteBundles();
    }

    public function createBundle(Request $request)
    {
        $bundle = new Bundle();
        $bundle->name = $request->input('name');
        $bundle->published = (bool) $request->input('published', false);
        $bundle->locked = (bool) $request->input('locked', false);
        $bundle->version = 1;
        $bundle->saveOrFail();

        return $bundle;
    }

    public function updateBundle(Request $request, Bundle $bundle)
    {
        $bundle->name = $request->input('name');
        $bundle->published = (bool) $request->input('published', false);
        $bundle->locked = (bool) $request->input('locked', false);
        $bundle->version = $bundle->version + 1;
        $bundle->saveOrFail();

        return $bundle;
    }

    public function deleteBundle(Bundle $bundle)
    {
        $bundle->delete();
    }
}
