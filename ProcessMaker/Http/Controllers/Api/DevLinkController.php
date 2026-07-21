<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use ProcessMaker\Events\CustomizeUiUpdated;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Requests\DevLinkStoreRequest;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Jobs\CompileUI;
use ProcessMaker\Jobs\DevLinkInstall;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleAsset;
use ProcessMaker\Models\BundleInstance;
use ProcessMaker\Models\BundleSetting;
use ProcessMaker\Models\DevLink;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\BundleUpdatedNotification;
use ProcessMaker\Package\PackageDynamicUI\Models\Dashboard;
use ProcessMaker\Package\PackageDynamicUI\Models\Menu;

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

    public function store(DevLinkStoreRequest $request)
    {
        $normalizedUrl = $this->normalizeUrl($request->input('url'));
        $existingDevLink = DevLink::query()
            ->get(['id', 'name', 'url'])
            ->first(fn (DevLink $devLink) => $this->normalizeUrl($devLink->url) === $normalizedUrl);
        if ($existingDevLink) {
            throw ValidationException::withMessages([
                'url' => __(
                    'This instance is already linked as :name. Open or reconnect the existing connection.',
                    ['name' => $existingDevLink->name]
                ),
            ]);
        }

        $devLink = new DevLink();
        $devLink->name = $request->input('name');
        $devLink->url = $normalizedUrl;
        $devLink->saveOrFail();

        return $devLink;
    }

    public function update(Request $request, DevLink $devLink)
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
        ]);
        $request->validate([
            'name' => ['required', 'string', Rule::unique('dev_links', 'name')->ignore($devLink->id)],
        ]);

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
        try {
            return $devLink->client()->get(route('api.devlink.pong', [], false));
        } catch (\Exception $e) {
            return response()->json(['error' => 'DevLink connection error'], $e->getCode());
        }
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
        return $bundle->load('assets')->load('settings');
    }

    public function remoteBundles(Request $request, DevLink $devLink)
    {
        return $devLink->remoteBundles($request->input('filter'));
    }

    public function createBundle(Request $request)
    {
        $bundle = new Bundle();
        $bundle->name = $request->input('name');
        $bundle->description = $request->input('description');
        $bundle->published = (bool) $request->input('published', false);
        $bundle->version = 1;
        $bundle->saveOrFail();

        return $bundle;
    }

    public function updateBundle(Request $request, Bundle $bundle)
    {
        $bundle->validateEditable();

        $bundle->name = $request->input('name');
        $bundle->description = $request->input('description');
        $bundle->published = (bool) $request->input('published', false);
        $bundle->saveOrFail();

        return $bundle;
    }

    public function increaseBundleVersion(Bundle $bundle)
    {
        $bundle->notifyBundleUpdated();

        $bundle->version = $bundle->version + 1;
        $bundle->saveOrFail();

        return $bundle;
    }

    public function bundleUpdated(int $bundleId, string $token)
    {
        try {
            $bundle = Bundle::where('remote_id', $bundleId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Bundle not found'], 403);
        }

        $storedToken = $bundle->webhook_token;

        if ($token !== $storedToken) {
            return response()->json(['error' => 'Invalid token'], 403);
        }
        $adminUsers = User::where('is_administrator', true)->get();

        Notification::send($adminUsers, new BundleUpdatedNotification($bundle));

        return $bundle;
    }

    public function deleteBundle(Bundle $bundle)
    {
        $bundle->assets()->delete();
        $bundle->settings()->delete();
        $bundle->instances()->delete();
        $bundle->delete();
    }

    public function installRemoteBundle(Request $request, DevLink $devLink, int $remoteBundleId)
    {
        $updateType = $request->input('updateType', DevLinkInstall::MODE_UPDATE);
        DevLinkInstall::dispatch(
            $request->user()->id,
            $devLink->id,
            Bundle::class,
            $remoteBundleId,
            $updateType,
            DevLinkInstall::TYPE_INSTALL_BUNDLE,
        );

        return [
            'status' => 'queued',
        ];
    }

    public function reinstallBundle(Request $request, Bundle $bundle)
    {
        $updateType = $request->input('updateType', DevLinkInstall::MODE_UPDATE);
        DevLinkInstall::dispatch(
            $request->user()->id,
            $bundle->dev_link_id,
            Bundle::class,
            $bundle->id,
            $updateType,
            DevLinkInstall::TYPE_REINSTALL_BUNDLE,
        );

        return [
            'status' => 'queued',
        ];
    }

    public function addBundleInstance(Request $request, Bundle $bundle)
    {
        BundleInstance::create([
            'bundle_id' => $bundle->id,
            'instance_url' => $request->input('instance_url'),
        ]);
    }

    public function exportLocalBundle(Bundle $bundle)
    {
        return ['payloads' => $bundle->export()];
    }

    public function exportLocalBundleSettings(Bundle $bundle)
    {
        return ['settings' => $bundle->exportSettings()];
    }

    public function exportLocalBundleSettingPayloads(Bundle $bundle)
    {
        if ($bundle->settings->isEmpty()) {
            return ['payloads' => []];
        }

        return ['payloads' => $bundle->exportSettingPayloads()];
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

    public function addSettings(Request $request, Bundle $bundle)
    {
        $bundle->addSettings(
            $request->input('setting'),
            $request->input('config'),
            $request->input('type'),
            $request->input('replaceIds', false)
        );
    }

    public function addAssetToBundles(Request $request)
    {
        $bundles = $request->input('bundles');
        foreach ($bundles as $id) {
            $bundle = Bundle::find($id);
            if ($bundle) {
                $asset = $request->input('type')::findOrFail($request->input('id'));
                $bundle->addAssetToBundles($asset);
            }
        }
    }

    public function addSettingToBundles(Request $request)
    {
        $bundles = $request->input('bundles');
        foreach ($bundles as $id) {
            $bundle = Bundle::find($id);
            if ($bundle) {
                $bundle->addSettingToBundles($request->input('setting'), $request->input('config'), $request->input('type'));
            }
        }
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

    public function removeSharedAsset(int $id)
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
        $updateType = $request->input('updateType', DevLinkInstall::MODE_UPDATE);

        DevLinkInstall::dispatch(
            $request->user()->id,
            $devLink->id,
            $request->input('class'),
            $request->input('id'),
            $updateType,
            DevLinkInstall::TYPE_IMPORT_ASSET
        );

        return [
            'status' => 'queued',
        ];
    }

    public function remoteBundleVersion(DevLink $devLink, int $remoteBundleId)
    {
        try {
            $payload = $devLink->remoteBundle($remoteBundleId)->json();
        } catch (RequestException|ConnectionException $e) {
            return [
                'available' => false,
                'version' => null,
            ];
        }

        if (!is_array($payload)) {
            return [
                'available' => false,
                'version' => null,
            ];
        }

        $payload['available'] = true;

        return $payload;
    }

    private function normalizeUrl(string $url): string
    {
        $parts = parse_url(trim($url));
        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? null;

        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        return $scheme . '://' . $host . ($port === null ? '' : ':' . $port);
    }

    public function deleteBundleAsset(BundleAsset $bundleAsset)
    {
        $bundleAsset->delete();

        return response()->json(['message' => 'Bundle asset association deleted.'], 200);
    }

    public function deleteBundleSetting(BundleSetting $bundleSetting)
    {
        $bundleSetting->delete();

        return response()->json(['message' => 'Bundle setting deleted.'], 200);
    }

    public function getBundleSetting(Bundle $bundle, string $settingKey)
    {
        return $bundle->settings()->where('setting', $settingKey)->first();
    }

    public function getBundleSettingPreview(Bundle $bundle, string $settingKey): array
    {
        abort_unless(in_array($settingKey, ['ui_dashboards', 'ui_menus'], true), 404);

        return $bundle->settingPreview($settingKey);
    }

    public function getBundleAllSettings(string $settingKey)
    {
        return match ($settingKey) {
            'ui_dashboards' => $this->getAvailableDashboards(),
            'ui_menus' => $this->getAvailableMenus(),
            'ui_settings' => Setting::whereIn('key', ['css-override', 'login-footer', 'logo-alt-text'])->get(),
            default => Setting::where([
                ['group_id', SettingsMenus::getId($settingKey)],
                ['hidden', 0],
            ])->get(),
        };
    }

    private function getAvailableDashboards()
    {
        if (!class_exists(Dashboard::class)) {
            return [];
        }

        return Dashboard::query()
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(function (Dashboard $dashboard) {
                return [
                    'key' => $dashboard->id,
                    'name' => $dashboard->title,
                ];
            })
            ->values();
    }

    private function getAvailableMenus()
    {
        if (!class_exists(Menu::class)) {
            return [];
        }

        return Menu::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Menu $menu) {
                return [
                    'key' => $menu->id,
                    'name' => $menu->name,
                ];
            })
            ->values();
    }

    public function refreshUi(Request $request)
    {
        CompileUI::dispatch($request->user()?->id);
        CustomizeUiUpdated::dispatch([], [], false);
    }
}
