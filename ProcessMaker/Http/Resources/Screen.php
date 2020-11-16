<?php

namespace ProcessMaker\Http\Resources;

use ProcessMaker\ScreenConsolidator;

class Screen extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $screen = parent::toArray($request);
        $consolidator = new ScreenConsolidator($this);
        return array_merge($screen, $consolidator->call());
    }
}