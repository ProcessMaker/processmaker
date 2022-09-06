<?php

namespace ProcessMaker\Http\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 *  @OA\Schema(
 *    schema="metadata",
 *    @OA\Property(property="filter", type="string"),
 *    @OA\Property(property="sort_by", type="string"),
 *    @OA\Property(property="sort_order", type="string", enum={"asc", "desc"}),
 *    @OA\Property(property="count", type="integer"),
 *    @OA\Property(property="total_pages", type="integer"),
 *
 *    @OA\Property(property="current_page", type="integer"),
 *    @OA\Property(property="form", type="integer"),
 *    @OA\Property(property="last_page", type="integer"),
 *    @OA\Property(property="path", type="string"),
 *    @OA\Property(property="per_page", type="integer"),
 *    @OA\Property(property="to", type="integer"),
 *    @OA\Property(property="total", type="integer"),
 *  )
 */
class ApiCollection extends ResourceCollection
{
    public $appends = [];

    protected $appended;

    protected $totalCount;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource, $total = null)
    {
        parent::__construct($resource);

        if ($total !== null) {
            $this->totalCount = (int) $total;
        }

        $this->appended = (object) [];

        foreach ($this->appends as $property) {
            if (property_exists($resource, $property)) {
                $this->appended->{$property} = $resource->{$property};
            }
        }
    }

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
                'filter' => htmlentities($request->input('filter', '')),
                'sort_by' => $request->input('order_by', ''),
                'sort_order' => $request->input('order_direction', ''),
                /**
                 * count: (integer, total items in current response)
                 */
                'count' => $this->resource->count(),
                /**
                 * total_pages: (integer, the total number of pages available, based on per_page and total)
                 */
                'total_pages' => ceil($this->resource->total() / $this->resource->perPage()),
            ],
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
        if ($this->resource instanceof Collection) {
            $this->resource = $this->collectionToPaginator($this->resource, $request);
        }

        return $this->resource instanceof AbstractPaginator
                    ? (new ApiPaginatedResourceResponse($this))->toResponse($request)
                    : parent::toResponse($request);
    }

    /**
     * Convert a Collection to a LengthAwarePaginator
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function collectionToPaginator(Collection $collection, Request $request)
    {
        if ($this->totalCount === null) {
            $count = $collection->count();
        } else {
            $count = $this->totalCount;
        }

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        $startIndex = ($page - 1) * $perPage;
        $limit = $perPage;

        if ($this->collection->count() > $limit) {
            $this->collection = $collection->slice($startIndex, $limit)->values();
        }

        return new LengthAwarePaginator($this->collection, $count, $perPage);
    }

    /**
     * Return the total count.
     *
     * @return int
     */
    public function getTotal()
    {
        if ($this->totalCount !== null) {
            return $this->totalCount;
        } else {
            $resource = $this->resource->toArray();
            if (isset($resource['total'])) {
                return $resource['total'];
            }
        }

        return null;
    }
}
