<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Http\Resources\Json\PaginatedResourceResponse;

class ApiPaginatedResourceResponse extends PaginatedResourceResponse {
    /**
     * Add the pagination information to the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function paginationInformation($request)
    {
        $paginated = $this->resource->resource->toArray();
        return [
            'meta' => $this->meta($paginated),
        ];
    }
}

?>