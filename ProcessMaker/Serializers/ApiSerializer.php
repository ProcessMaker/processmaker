<?php

namespace ProcessMaker\Serializers;

use Illuminate\Http\Request;
use League\Fractal\Pagination\CursorInterface;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Serializer\SerializerAbstract;

/**
 * Serializer for ProcessMaker API.
 *
 */
class ApiSerializer extends SerializerAbstract
{
    /**
     * Serialize a collection.
     *
     * @param string $resourceKey
     * @param array $data
     *
     * @return array
     */
    public function collection($resourceKey, array $data)
    {
        if ($resourceKey === false) {
            return $data;
        }
        return array($resourceKey ?: 'data' => $data);
    }

    /**
     * Serialize an item.
     *
     * @param string $resourceKey
     * @param array $data
     *
     * @return array
     */
    public function item($resourceKey, array $data): array
    {
        return $data;
    }

    /**
     * Serialize null resource.
     *
     * @return array
     */
    public function null()
    {
        return [];
    }

    /**
     * Serialize the included data.
     *
     * @param ResourceInterface $resource
     * @param array $data
     *
     * @return array
     *
     * @codeCoverageIgnore SideloadIncludes not used for this serializer.
     */
    public function includedData(ResourceInterface $resource, array $data): array
    {
        return $data;
    }

    /**
     * Serialize the meta.
     *
     * @param array $meta
     *
     * @return array
     */
    public function meta(array $meta): array
    {
        if (empty($meta)) {
            return [];
        }

        return $meta['pagination'];
    }

    /**
     * Serialize the paginator.
     *
     * @param PaginatorInterface $paginator
     *
     * @return array
     */
    public function paginator(PaginatorInterface $paginator): array
    {
        //Get data of query parameter filter, sort_by, sort_order
        // Build the Request from Laravel, not directly from url
        $request = app()->make(Request::class);

        $pagination['meta'] = (object)[
            'total' => (int)$paginator->getTotal(),
            'count' => (int)$paginator->getCount(),
            'per_page' => (int)$paginator->getPerPage(),
            'current_page' => (int)$paginator->getCurrentPage(),
            'total_pages' => (int)$paginator->getLastPage(),
            'filter' => $request->input('filter', ''),
            'sort_by' => $request->input('order_by', ''),
            'sort_order' => $request->input('order_direction', '')
        ];

        return ['pagination' => $pagination];
    }

    /**
     * Serialize the cursor.
     *
     * @param CursorInterface $cursor
     *
     * @return array
     */
    public function cursor(CursorInterface $cursor): array
    {
        $position = [
            'start' => $cursor->getCurrent(),
            'limit' => $cursor->getCount(),
        ];

        return ['pagination' => $position];
    }

}
