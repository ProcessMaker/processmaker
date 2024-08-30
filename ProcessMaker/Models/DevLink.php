<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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
        return Http::withToken($this->access_token)->baseUrl($this->url);
    }

    private function generateNewState()
    {
        $uuid = (string) Str::orderedUuid();
        $this->state = $uuid;
        $this->saveOrFail();

        return $uuid;
    }
}
