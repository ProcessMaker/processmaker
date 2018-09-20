<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class ApiCollection extends ResourceCollection
{
    /**
     * Generic collection to add sorting and filtering metadata.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $payload = [
            'data' => $this->collection,
            'meta' => [
                'filter' => $request->input('filter', ''),
                'sort_by' => $request->input('order_by', ''),
                'sort_order' => $request->input('order_direction', ''),
                /**
                 * count: (integer, total items in current response)
                 */
                'count' => $this->resource->count(),
                /**
                 * total_pages: (integer, the total number of pages available, based on per_page and total)
                 */
                'total_pages' => ceil($this->resource->total() / $this->resource->perPage())
            ]
        ];

        return $payload;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        return $this->resource instanceof AbstractPaginator
                    ? (new ApiPaginatedResourceResponse($this))->toResponse($request)
                    : parent::toResponse($request);
    }
}
