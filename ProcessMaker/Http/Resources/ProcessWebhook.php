<?php
namespace ProcessMaker\Http\Resources;

class ProcessWebhook extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $array = parent::toArray($request);
        if (!empty($array)) {
            $array['url'] = $this->url();
        }
        return $array;
    }
}
