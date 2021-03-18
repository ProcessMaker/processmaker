<?php

namespace ProcessMaker\Bpmn;

use Illuminate\Support\Facades\Cache;
use ProcessMaker\Nayra\Bpmn\Models\Process as ModelsProcess;

class Process extends ModelsProcess
{

    /**
     * Get a property.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getProperty($name, $default = null)
    {
        if ($name === 'conditionals') {
            $key = '_process_' . $this->getOwnerDocument()->getModel()->getKey();
            $properties = Cache::store('global_variables')->get($key, []);
            return $properties[$name] ?? $default;
        } else {
            return parent::getProperty($name, $default);
        }
    }

    /**
     * Set a property.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setProperty($name, $value)
    {
        if ($name === 'conditionals') {
            $key = '_process_' . $this->getOwnerDocument()->getModel()->getKey();
            $properties = Cache::store('global_variables')->get($key, []);
            $properties[$name] = $value;
            \Log::info(['global_variables', $key, $properties]);
            Cache::store('global_variables')->forever($key, $properties);
            return $this;
        } else {
            return parent::setProperty($name, $value);
        }
    }

    /**
     * Return true if the process is not persistent
     *
     * @return boolean
     */
    public function isNonPersistent()
    {
        return strpos($this->getId(), 'non_persistent') !== false;
    }
}
