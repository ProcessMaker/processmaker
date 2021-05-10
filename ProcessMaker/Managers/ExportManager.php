<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Models\Screen;
use Illuminate\Database\Eloquent\Model;

class ExportManager
{
    private $dependencies = [];

    private $logMessages = [];

    /**
     * Get the value of dependencies
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Set the value of dependencies
     *
     * @return  self
     */
    public function setDependencies($dependencies)
    {
        $this->dependencies = $dependencies;

        return $this;
    }

    public function addDependency(array $dependency)
    {
        $this->dependencies[] = $dependency;
    }

    /**
     * Get dependencies of a $modelClass type
     *
     * @param string $modelClass
     * @param Model $owner
     * @param array $references
     *
     * @return array
     */
    public function getDependenciesOfType($modelClass, $owner, array $references = [])
    {
        $references = $this->reviewDependenciesOf($owner, $references);
        $ids = [];
        foreach ($references as $ref) {
            list($class, $id) = $ref;
            $class === $modelClass ? $ids[] = $id : null;
        }
        return array_unique($ids);
    }

    /**
     * Review all the dependencies of the $owner
     *
     * @param Model $owner
     * @param array $references
     * @param array $reviewed
     *
     * @return array
     */
    private function reviewDependenciesOf(Model $owner, array $references = [], array $reviewed = [])
    {
        $key = get_class($owner) . ':' . $owner->getKey();
        if (in_array($key, $reviewed)) {
            return $references;
        }
        $reviewed[] = $key;
        $newReferences = [];
        foreach ($this->dependencies as $dependencie) {
            if (is_a($owner, $dependencie['owner'])) {
                $recursive = false; // We search nested dependencies below
                $newReferences = call_user_func($dependencie['referencesToExport'], $owner, $newReferences, $this, $recursive);
            }
        }
        $newReferences = $this->uniqueDiff($newReferences, $references);
        $references = array_merge($references, $newReferences);
        // Find recurcively dependencies
        foreach ($newReferences as $ref) {
            list($class, $id) = $ref;
            $nextOwner = $class::find($id);
            if ($nextOwner) {
                $references = $this->reviewDependenciesOf($nextOwner, $references, $reviewed);
            }
        }
        return $references;
    }

    /**
     * Update references for a given model
     *
     * @param Model $model
     * @param array $newReferences
     *
     * @return Model
     */
    public function updateReferences(array $newReferences)
    {
        foreach ($newReferences as $class =>  $model) {
            if (is_array($model)) {
                foreach ($model as $item) {
                    $this->updateModelReferences($item, $newReferences);
                }
            } elseif (is_object($model) && $model instanceof Model) {
                $this->updateModelReferences($model, $newReferences);
            }
        }
    }

    private function updateModelReferences(Model $model, array $newReferences)
    {
        foreach ($this->dependencies as $dependencie) {
            if (is_a($model, $dependencie['owner']) && isset($dependencie['updateReferences'])) {
                call_user_func($dependencie['updateReferences'], $model, $newReferences, $this);
            }
        }
    }

    public function addDependencyManager($class)
    {
        if (is_string($class)) {
            $instance  = new $class;
        } else {
            $instance = $class;
        }

        $this->addDependency([
            'type' => $instance->type,
            'owner' => $instance->owner,
            'referencesToExport' => [$instance, 'referencesToExport'],
            'updateReferences' => [$instance, 'updateReferences'],
        ]);
        return $this;
    }

    /**
     * Remove duplicated items
     *
     * @param array $array
     *
     * @return array
     */
    private function uniqueDiff(array $array, array $references)
    {
        $result = [];
        foreach ($array as $item) {
            if (!in_array($item, $references) && !in_array($item, $result)) {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Add a log message about the import process
     *
     * @param string $key
     * @param string $label
     * @param bool $success
     * @param string $message
     * @return void
     */
    public function addLogMessage($key, $label, $success, $message)
    {
        $this->logMessages[$key] = \compact('label', 'success', 'message');
    }

    /**
     * Get logs of the current import process
     *
     * @return array
     */
    public function getLogMessages()
    {
        return $this->logMessages;
    }
}
