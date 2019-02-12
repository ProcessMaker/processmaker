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
        $array = ['webhook' => parent::toArray($request)];
        if (empty($array['webhook'])) {
            $array['webhook'] = null;
            return $array;
        }
        $array['webhook']['url'] = $this->url();
        return $array;
    }
}
