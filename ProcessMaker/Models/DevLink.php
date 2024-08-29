<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use ProcessMaker\Models\ProcessMakerModel;

class DevLink extends ProcessMakerModel
{
    use HasFactory;

    protected $guarded = [];

    public function getClientUrl()
    {
        $params = [
            'devlink_id' => $this->id,
            'redirect_url' => route('devlink.index'),
        ];

        return $this->url . route('devlink.oauth-client', $params, false);
    }

    public function getOauthRedirectUrl()
    {
        $params = http_build_query([
            'client_id' => $this->client_id,
            'redirect_url' => route('devlink.index'),
            'resource_type' => 'code',
            'state' => $this->generateNewState(),
        ]);

        return $this->url . '/oauth/authorize?' . $params;
    }

    private function generateNewState()
    {
        $uuid = (string) Str::orderedUuid();
        $this->state = $uuid;
        $this->saveOrFail();

        return $uuid;
    }
}
