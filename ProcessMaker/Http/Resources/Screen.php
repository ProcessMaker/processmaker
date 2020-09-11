<?php

namespace ProcessMaker\Http\Resources;

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
        
        $nested = [];
        foreach ($this->nestedScreenIds() as $id) {
            $nested[] = self::findOrFail($id)->toArray();
        }
        $screen['nested'] = $nested;

        return $screen;
    }
}