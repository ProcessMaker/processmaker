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

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($devLink) {
            foreach ($devLink->bundles as $bundle) {
                $bundle->delete();
            }
        });
    }

    public function getClientUrl()
    {
        $params = [
            'devlink_id' => $this->id,
            'redirect_uri' => route('devlink.index'),
        ];

        return $this->url . route('devlink.oauth-client', $params, false);
    }

    public function getOauthRedirectUrl()
    {
        $params = http_build_query([
            'client_id' => $this->client_id,
            'redirect_uri' => route('devlink.index'),
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

    public function remoteBundles($request)
    {
        return $this->client()->get(
            route('api.devlink.local-bundles',
                ['published' => true,
                    'filter' => $request->input('filter')],
                false)
        );
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
            route($request->input('url'),
                ['filter' => $request->input('filter')],
                false)
        );
    }

    public function installRemoteBundle($bundleId)
    {
        if (!$this->logger) {
            $this->logger = new Logger();
        }

        $this->logger->status(__('Downloading bundle from remote instance'));

        $bundleInfo = $this->remoteBundle($bundleId)->json();

        $bundleExport = $this->client()->get(
            route('api.devlink.export-local-bundle', ['bundle' => $bundleId], false)
        )->json();

        $bundle = Bundle::updateOrCreate(
            [
                'remote_id' => $bundleId,
                'dev_link_id' => $this->id,
            ],
            [
                'name' => $bundleInfo['name'],
                'published' => $bundleInfo['published'],
                'locked' => $bundleInfo['locked'],
                'version' => $bundleInfo['version'],
            ]
        );

        $this->logger->status('Installing bundle on the this instance');
        $this->logger->setSteps($bundleExport['payloads']);

        $assets = [];
        foreach ($bundleExport['payloads'] as $payload) {
            $assets[] = $this->import($payload);
        }

        $this->logger->status('Syncing bundle assets');
        $bundle->syncAssets($assets);

        $this->logger->addWarning('Test warning message');

        $this->logger->setStatus('done');
    }

    private function import(array $payload)
    {
        $importer = new Importer($payload, new Options([]), $this->logger);
        $manifest = $importer->doImport();

        return $manifest[$payload['root']]->model;
    }

    public function installRemoteAsset(string $class, int $id) : ProcessMakerModel
    {
        $payload = $this->client()->get(
            route('api.devlink.export-local-asset', ['class' => $class, 'id' => $id], false)
        )->json();

        return $this->import($payload);
    }

    public function bundles()
    {
        return $this->hasMany(Bundle::class);
    }
}
