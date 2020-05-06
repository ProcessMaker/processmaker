<?php
namespace ProcessMaker\Models;

use ProcessMaker\Nayra\Bpmn\DataStoreTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\DataStoreInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ItemDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;

/**
 * Application Data
 *
 * @package ProcessMaker\Models
 */
class DataStore implements DataStoreInterface
{

    use DataStoreTrait;

    private $data = [];

    private $updated = [];

    private $removed = [];

    /**
     *
     * @var \ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface
     */
    private $process;

    /**
     *
     * @var \ProcessMaker\Nayra\Contracts\Bpmn\ItemDefinitionInterface
     */
    private $itemSubject;

    /**
     * Get owner process.
     *
     * @return ProcessInterface
     */
    public function getOwnerProcess()
    {
        return $this->process;
    }

    /**
     * Get Process of the application.
     *
     * @param \ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface $process
     *
     * @return ProcessInterface
     */
    public function setOwnerProcess(ProcessInterface $process)
    {
        $this->process = $process;
        return $this;
    }

    /**
     * Get data from store.
     *
     * @param mixed $name
     *
     * @return mixed
     */
    public function getData($name = null, $default = null)
    {
        return $name === null ? $this->data : (isset($this->data[$name]) ? $this->data[$name] : $default);
    }

    /**
     * Set data of the store.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Put data to store.
     *
     * @param string $name
     * @param mixed $data
     *
     * @return $this
     */
    public function putData($name, $data)
    {
        $this->data[$name] = $data;
        $this->updated[$name] = $name;
        unset($this->removed[$name]);
        return $this;
    }

    /**
     * Get the items that are stored or conveyed by the ItemAwareElement.
     *
     * @return ItemDefinitionInterface
     */
    public function getItemSubject()
    {
        return $this->itemSubject;
    }

    /**
     * Remove data from store.
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeData($name)
    {
        unset($this->data[$name]);
        $this->removed[$name] = $name;
        unset($this->updated[$name]);
        return $this;
    }

    /**
     * Get the value of updated
     */ 
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get the value of removed
     */ 
    public function getRemoved()
    {
        return $this->removed;
    }

    /**
     * Update the data of an array based on the changes made in the data store
     *
     * @param array $array
     *
     * @return array
     */
    public function updateArray(array $array)
    {
        foreach($this->updated as $name) {
            $array[$name] = $this->data[$name];
        }
        foreach($this->removed as $name) {
            unset($array[$name]);
        }
        return $array;
    }
}
