<?php
namespace ProcessMaker\Http\Resources;

class ProcessWebEntry extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $array = ['web_entry' => parent::toArray($request)];
        if (empty($array['web_entry'])) {
            $array['web_entry'] = null;
            return $array;
        }
        $array['web_entry']['url'] = $this->url();
        return $array;
    }
}
