<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Models\ProcessMakerModel;

class DevLink extends ProcessMakerModel
{
    use HasFactory;

    public function getClientUrl()
    {
        $params = [
            'devlink_id' => $this->id,
            'redirect_url' => route('devlink.index'),
        ];

        return $this->url . route('devlink.oauth-client', $params, false);
    }
}
