<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Collection;

class IndexManager
{
    private $indexes;

    public function __construct()
    {
        $this->indexes = new Collection([]);
    }

    /**
     * Register index
     *
     * @param $name string name of index
     * @param $model string path of model
     * @param $callback callback function to perform indexing
     */
    public function add($name, $model, $callback = null)
    {
        $this->indexes->push((object) [
            'name' => $name,
            'model' => $model,
            'callback' => $callback,
        ]);
    }

    /**
     * Get an index by name
     *
     * @param $name string name of index
     */
    public function get($name)
    {
        return $this->indexes->where('name', $name)->first();
    }

    /**
     * List indexes
     *
     */
    public function list()
    {
        return $this->indexes;
    }

}