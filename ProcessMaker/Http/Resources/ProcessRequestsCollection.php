<?php

namespace ProcessMaker\Http\Resources;

class ProcessRequestsCollection extends ApiCollection
{
    public function toResponse($request)
    {
        $this->resource = $this->resource->reject(function($processRequest) {
            return $processRequest->process->category &&
                $processRequest->process->category->is_system;
            return false;
        });

        return parent::toResponse($request);
    }
}
