<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\ProcessMakerModel;
use Ramsey\Uuid\Type\Integer;
use ZipArchive;

class DevLink extends ProcessMakerModel
{
    use HasFactory;

    protected $guarded = [];

    // Do not send these to the frontend
    protected $hidden = [
        'client_secret',
        'access_token',
        'refresh_token',
        'state',
    ];

    protected $appends = ['redirect_uri'];

    protected $casts = [
        'client_secret' => 'encrypted',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($devLink) {
            foreach ($devLink->bundles as $bundle) {
                $bundle->delete();
            }
        });
    }

    public function getRedirectUriAttribute()
    {
        return route('devlink.index');
    }

    public function getClientUrl()
    {
        $params = [
            'devlink_id' => $this->id,
            'redirect_uri' => $this->redirect_uri,
        ];

        return $this->url . route('devlink.oauth-client', $params, false);
    }

    public function getOauthRedirectUrl()
    {
        $params = http_build_query([
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'state' => $this->generateNewState(),
        ]);

        return $this->url . '/oauth/authorize?' . $params;
    }

    public function client()
    {
        return Http::withToken($this->access_token)->baseUrl($this->url)->throw();
    }

    private function generateNewState()
    {
        $uuid = (string) Str::orderedUuid();
        $this->state = $uuid;
        $this->saveOrFail();

        return $uuid;
    }

    public function remoteBundles(string|null $filter)
    {
        $params = [
            'published' => true,
        ];

        if (!empty($filter)) {
            $params['filter'] = $filter;
        }

        $result = $this->client()->get(
            route('api.devlink.local-bundles', $params, false)
        )->json();

        $existingBundleRemoteIds = Bundle::where('dev_link_id', $this->id)->pluck('remote_id')->toArray();

        // add is_installed property to the data
        $result['data'] = array_map(function ($bundle) use ($existingBundleRemoteIds) {
            $bundle['is_installed'] = in_array($bundle['id'], $existingBundleRemoteIds);

            return $bundle;
        }, $result['data']);

        return $result;
    }

    public function remoteAssets($request)
    {
        return $this->client()->get(
            route('api.devlink.shared-assets',
                [],
                false)
        );
    }

    public function remoteBundle($bundleId)
    {
        return $this->client()->get(
            route('api.devlink.local-bundle', ['bundle' => $bundleId], false)
        );
    }

    public function remoteAssetsListing($request)
    {
        return $this->client()->get(
            route($request->input('url'), $request->all(), false)
        );
    }

    public function installRemoteBundle($remoteBundleId, $updateType)
    {
        if (!$this->logger) {
            $this->logger = new Logger();
        }

        $this->logger->status(__('Downloading bundle from remote instance'));

        $bundleInfo = $this->remoteBundle($remoteBundleId)->json();

        $bundleExport = $this->client()->get(
            route('api.devlink.export-local-bundle', ['bundle' => $remoteBundleId], false)
        )->json();

        $bundleSettingsExport = $this->client()->get(
            route('api.devlink.export-local-bundle-settings', ['bundle' => $remoteBundleId], false)
        )->json();

        $bundleSettingsPayloads = $this->client()->get(
            route('api.devlink.export-local-bundle-setting-payloads', ['bundle' => $remoteBundleId], false)
        )->json();

        $bundle = Bundle::updateOrCreate(
            [
                'remote_id' => $remoteBundleId,
                'dev_link_id' => $this->id,
            ],
            [
                'name' => $bundleInfo['name'],
                'published' => $bundleInfo['published'],
                'version' => $bundleInfo['version'],
                'description' => $bundleInfo['description'],
            ]
        );
        if ($bundle->wasRecentlyCreated) {
            $token = Str::random(60);

            $addBundleInstance = $this->client()->post(
                route('api.devlink.add-bundle-instance', ['bundle' => $remoteBundleId], false),
                [
                    'instance_url' => env('APP_URL') . '/devlink/bundle-updated/' . $remoteBundleId . '/' . $token,
                ]
            );
            $bundle->webhook_token = $token;
            $bundle->save();
        }

        $bundle->savePayloadsToFile($bundleExport['payloads'], $bundleSettingsPayloads['payloads']);

        $bundle->install($bundleExport['payloads'], $updateType, $this->logger);
        $bundle->installSettingsPayloads($bundleSettingsPayloads['payloads'], $updateType, $this->logger);
        $bundle->installSettings($bundleSettingsExport['settings']);

        $this->logger->setStatus('done');
    }

    public function installRemoteAsset(string $class, int $id, Logger $logger) : ProcessMakerModel
    {
        $payload = $this->client()->get(
            route('api.devlink.export-local-asset', ['class' => $class, 'id' => $id], false)
        )->json();

        $options = new Options([
            'mode' => 'update',
        ]);

        $logger->setSteps([$payload]);

        $model = self::import($payload, $options, $logger);

        $logger->setStatus('done');

        return $model;
    }

    public function bundles()
    {
        return $this->hasMany(Bundle::class);
    }

    public static function import(array $payload, Options $options, Logger $logger)
    {
        $importer = new Importer($payload, $options, $logger);
        $manifest = $importer->doImport();

        return $manifest[$payload['root']]->model;
    }
}
