<?php

namespace ProcessMaker\Http\Resources;

class ProcessRequestsCollection extends ApiCollection
{
    public function toResponse($request)
    {
        $this->resource->each(function($processRequest, $key) {
            if ($processRequest->isSystemResource()) {
                $this->resource->forget($key);
            }
        });

        return parent::toResponse($request);
    }
}
