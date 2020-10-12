<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Facades\Cache;
use ProcessMaker\Nayra\Bpmn\DataStoreTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\DataStoreInterface;

/**
 * Application Data
 *
 * @package ProcessMaker\Models
 */
class GlobalDataStore implements DataStoreInterface
{
    use DataStoreTrait;

    /**
     * Get data from store.
     *
     * @param $name
     * @param $default
     *
     * @return mixed
     */
    public function getData($name = null, $default = null)
    {
        if ($name === null) {
            return Cache::store('global_variables')->get('global', []);
        } else {
            return Cache::store('global_variables')->get($name, $default);
        }
    }

    /**
     * Set data of the store.
     *
     * @param $data
     *
     * @return $this
     */
    public function setData($data)
    {
        Cache::store('global_variables')->forever('global', $data);
        return $this;
    }

    /**
     * Put data to store.
     *
     * @param $name
     * @param $data
     *
     * @return $this
     */
    public function putData($name, $data)
    {
        return Cache::store('global_variables')->forever($name, $data);
    }
}
