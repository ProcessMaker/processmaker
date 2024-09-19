<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\ProcessMakerModel;

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

    public function remoteBundles()
    {
        return $this->client()->get(
            route('api.devlink.local-bundles', ['published' => true], false)
        );
    }

    public function installRemoteBundle($bundleId)
    {
        $bundleInfo = $this->client()->get(
            route('api.devlink.local-bundle', ['bundle' => $bundleId], false)
        )->json();

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

        $assets = [];
        foreach ($bundleExport['payloads'] as $payload) {
            $importer = new Importer($payload, new Options([]));
            $manifest = $importer->doImport();
            $assets[] = $manifest[$payload['root']]->model;
        }

        $bundle->syncAssets($assets);
    }
}
