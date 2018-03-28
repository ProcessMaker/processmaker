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
     * @param array  $data
     *
     * @return array
     */
    public function collection($resourceKey, array $data)
    {
        if ($this->isPaged()) {
            return ["data"=> $data];
        } else {
            return $data;
        }
    }

    /**
     * Serialize an item.
     *
     * @param string $resourceKey
     * @param array  $data
     *
     * @return array
     */
    public function item($resourceKey, array $data)
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
     * @param array             $data
     *
     * @return array
     *
     * @codeCoverageIgnore SideloadIncludes not used for this serializer.
     */
    public function includedData(ResourceInterface $resource, array $data)
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
    public function meta(array $meta)
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
    public function paginator(PaginatorInterface $paginator)
    {
        $pagination = [
            'start'        => ($paginator->getCurrentPage() - 1) * $paginator->getPerPage(),
            'limit'     => (int) $paginator->getPerPage(),
            'total'        => (int) $paginator->getTotal(),
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
    public function cursor(CursorInterface $cursor)
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
