<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use ProcessMaker\Events\CustomizeUiUpdated;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Jobs\CompileSass;
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
            'name' => ['required'],
            'url' => ['required', 'url'],
        ]);
        $devLink = DevLink::where('name', $request->input('name'))->first();
        if ($devLink) {
            $devLink->url = $request->input('url');
        } else {
            $devLink = new DevLink();
            $devLink->name = $request->input('name');
            $devLink->url = $request->input('url');
        }
        $devLink->saveOrFail();

        return $devLink;
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
        try {
            return$devLink->client()->get(route('api.devlink.pong', [], false));
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

    public function bundleUpdated($bundleId, $token)
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

    public function installRemoteBundle(Request $request, DevLink $devLink, $remoteBundleId)
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
        DevLinkInstall::dispatch(
            $request->user()->id,
            $devLink->id,
            $request->input('class'),
            $request->input('id'),
            DevLinkInstall::MODE_UPDATE,
            DevLinkInstall::TYPE_IMPORT_ASSET
        );

        return [
            'status' => 'queued',
        ];
    }

    public function remoteBundleVersion(DevLink $devLink, $remoteBundleId)
    {
        return $devLink->remoteBundle($remoteBundleId);
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

    public function getBundleSetting(Bundle $bundle, $settingKey)
    {
        $setting = $bundle->settings()->where('setting', $settingKey)->first();

        return $setting;
    }

    public function getBundleAllSettings($settingKey)
    {
        if ($settingKey === 'ui_settings') {
            return Setting::whereIn('key', ['css-override', 'login-footer', 'logo-alt-text'])
                         ->get();
        }

        return Setting::where([
            ['group_id', SettingsMenus::getId($settingKey)],
            ['hidden', 0],
        ])->get();
    }

    public function refreshUi()
    {
        $cssOverride = Setting::where('key', 'css-override')->first();

        $config = $cssOverride->config;
        $variables = json_decode($config['variables']);
        $sansSerif = json_decode($config['sansSerifFont'], true);

        $this->writeColors($variables);
        $this->writeFonts($sansSerif);
        $this->compileSass(auth()->user()->id);
        CustomizeUiUpdated::dispatch([], [], false);
    }

    private function writeColors($data)
    {
        // Now generate the _colors.scss file
        $contents = "// Changed theme colors\n";
        foreach ($data as $value) {
            $contents .= $value->id . ': ' . $value->value . ";\n";
        }
        File::put(app()->resourcePath('sass') . '/_colors.scss', $contents);
    }

    /**
     * Write variables font in file
     *
     * @param $sansSerif
     * @param $serif
     */
    private function writeFonts($sansSerif)
    {
        $sansSerif = $sansSerif ? $sansSerif : $this->sansSerifFontDefault();
        // Generate the _fonts.scss file
        $contents = "// Changed theme fonts\n";
        $contents .= '$font-family-sans-serif: ' . $sansSerif['id'] . " !default;\n";
        File::put(app()->resourcePath('sass') . '/_fonts.scss', $contents);
    }

    /**
     * run jobs compile
     */
    private function compileSass($userId)
    {
        // Compile the Sass files
        $this->dispatch(new CompileSass([
            'tag' => 'sidebar',
            'origin' => 'resources/sass/sidebar/sidebar.scss',
            'target' => 'public/css/sidebar.css',
            'user' => $userId,
        ]));
        $this->dispatch(new CompileSass([
            'tag' => 'app',
            'origin' => 'resources/sass/app.scss',
            'target' => 'public/css/app.css',
            'user' => $userId,
        ]));
        $this->dispatch(new CompileSass([
            'tag' => 'queues',
            'origin' => 'resources/sass/admin/queues.scss',
            'target' => 'public/css/admin/queues.css',
            'user' => $userId,
        ]));
    }
}
