<?php

namespace ProcessMaker\Http\Resources\V1_1;

use Illuminate\Http\Request;

class TaskInterstitialResource extends TaskResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        return $this->includeInterstitial();
    }
}
