<?php

namespace ProcessMaker\Transformers;

use League\Fractal\Pagination\CursorInterface;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Serializer\SerializerAbstract;

/**
 * Serializer for ProcessMaker API.
 *
 */
class ProcessMakerSerializer extends SerializerAbstract
{

    /**
     * True if the resource will have page information.
     *
     * @var bool $paged
     */
    private $paged = false;

    /**
     * ProcessMaker serializer constructor.
     *
     * @param bool $paged Used to specify that the response include page information.
     */
    public function __construct($paged = false)
    {
        $this->setPaged($paged);
    }

    /**
     * Serialize a collection.
     *
     * @param string $resourceKey
     * @param array $data
     *
     * @return array
     */
    public function collection($resourceKey, array $data): array
    {
        if ($this->isPaged()) {
            return ["data" => $data];
        } else {
            return $data;
        }
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
        parse_str(parse_url($paginator->getUrl(1), PHP_URL_QUERY), $data);

        $pagination['meta'] = (object)[
            'total' => (int)$paginator->getTotal(),
            'count' => (int)$paginator->getCount(),
            'per_page' => (int)$paginator->getPerPage(),
            'current_page' => (int)$paginator->getCurrentPage(),
            'total_pages' => (int)$paginator->getLastPage(),
            'filter' => isset($data['filter']) ? $data['filter'] : '',
            'sort_by' => isset($data['sort_by']) ? $data['sort_by'] : '',
            'sort_order' => isset($data['sort_order']) ? $data['sort_order'] : ''
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

    /**
     * Set if the resource will have page information.
     *
     * @param bool $paged
     */
    private function setPaged($paged)
    {
        $this->paged = $paged;
    }

    /**
     * True if the resource will have page information.
     *
     * @return bool
     */
    private function isPaged()
    {
        return $this->paged;
    }
}
