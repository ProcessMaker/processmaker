<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleAsset;
use ProcessMaker\Models\DevLink;
use ProcessMaker\Models\Setting;

class DevLinkController extends Controller
{
    public function index(Request $request)
    {
        $query = DevLink::query();
        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->where('id', 'like', $filter)
                    ->orWhere('name', 'like', $filter)
                    ->orWhere('url', 'like', $filter);
            });
        }
        $order_by = 'id';
        $order_direction = 'ASC';

        $response =
            $query->orderBy(
                $request->input('order_by', $order_by),
                $request->input('order_direction', $order_direction)
            )
            ->paginate($request->input('per_page', 15));

        return new ApiCollection($response);
    }

    public function show(DevLink $devLink)
    {
        return $devLink;
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
        $bundlesQuery = Bundle::with('devLink');

        if ($request->has('published')) {
            $bundlesQuery->published();
        }

        if ($request->has('editable')) {
            $bundlesQuery->editable();
        }

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $bundlesQuery->where(function ($bundlesQuery) use ($filter) {
                $bundlesQuery->where('id', 'like', $filter)
                    ->orWhere('name', 'like', $filter)
                    ->orWhere('version', 'like', $filter);
            });
        }
        $order_by = 'id';
        $order_direction = 'ASC';

        $response =
            $bundlesQuery->orderBy(
                $request->input('order_by', $order_by),
                $request->input('order_direction', $order_direction)
            )
            ->paginate($request->input('per_page', 15));

        return new ApiCollection($response);
    }

    public function showBundle(Bundle $bundle)
    {
        return $bundle->load('assets');
    }

    public function remoteBundles(Request $request, DevLink $devLink)
    {
        return $devLink->remoteBundles($request);
    }

    public function createBundle(Request $request)
    {
        $bundle = new Bundle();
        $bundle->name = $request->input('name');
        $bundle->published = (bool) $request->input('published', false);
        $bundle->version = 1;
        $bundle->saveOrFail();

        return $bundle;
    }

    public function updateBundle(Request $request, Bundle $bundle)
    {
        $bundle->validateEditable();

        $bundle->name = $request->input('name');
        $bundle->published = (bool) $request->input('published', false);
        $bundle->version = $bundle->version + 1;
        $bundle->saveOrFail();

        return $bundle;
    }

    public function deleteBundle(Bundle $bundle)
    {
        $bundle->delete();
    }

    public function installRemoteBundle(DevLink $devLink, $remoteBundleId)
    {
        return $devLink->installRemoteBundle($remoteBundleId);
    }

    public function exportLocalBundle(Bundle $bundle)
    {
        return ['payloads' => $bundle->export()];
    }

    public function exportLocalAsset(Request $request)
    {
        $asset = $request->input('class')::findOrFail($request->input('id'));

        return $asset->export();
    }

    public function addAsset(Request $request, Bundle $bundle)
    {
        $asset = $request->input('type')::findOrFail($request->input('id'));
        $bundle->addAsset($asset);
    }

    public function sharedAssets(Request $request)
    {
        return Setting::Where('group', 'Devlink')->get();
    }

    public function remoteAssets(Request $request, DevLink $devLink)
    {
        return $devLink->remoteAssets($request);
    }

    public function remoteAssetsListing(Request $request, DevLink $devLink)
    {
        return $devLink->remoteAssetsListing($request);
    }

    public function addSharedAsset(Request $request)
    {
        $sharedAsset = new Setting();
        $sharedAsset->key = $request->input('key');
        $sharedAsset->config = $request->input('config');
        $sharedAsset->name = $request->input('name');
        $sharedAsset->hidden = 0;
        $sharedAsset->group = 'Devlink';
        $sharedAsset->format = 'text';
        $sharedAsset->saveOrFail();
    }

    public function removeSharedAsset($id)
    {
        $deleted = Setting::destroy($id);

        if ($deleted) {
            return response()->json(['message' => 'Asset deleted.'], 200);
        } else {
            return response()->json(['message' => 'Asset not found.'], 404);
        }
    }

    public function installRemoteAsset(Request $request, DevLink $devLink)
    {
        return $devLink->installRemoteAsset(
            $request->input('class'),
            $request->input('id')
        );
    }

    public function deleteBundleAsset(BundleAsset $bundleAsset)
    {
        $bundleAsset->delete();

        return response()->json(['message' => 'Bundle asset association deleted.'], 200);
    }
}
