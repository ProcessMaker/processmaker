<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Repositories\BpmnDocument;

trait ProcessTrait
{
    /**
     * Parsed process BPMN definitions.
     *
     * @var \ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface
     */
    private $bpmnDefinitions;

    /**
     * Get the process definitions from BPMN field.
     *
     * @param bool $forceParse
     *
     * @return BpmnDocument
     */
    public function getDefinitions($forceParse = false, $engine = null)
    {
        if ($forceParse || empty($this->bpmnDefinitions)) {
            $version = $this instanceof ProcessVersion ? $this : null;
            $options = [
                'process' => $this instanceof ProcessVersion ? $this->process : $this,
                'process_version' => $version,
            ];
            !$engine ?: $options['engine'] = $engine;
            $this->bpmnDefinitions = app(BpmnDocumentInterface::class, $options);
            if ($this->bpmn) {
                $this->bpmnDefinitions->loadXML($this->bpmn);
            }
        }

        return $this->bpmnDefinitions;
    }

    /**
     * Get BPMN DOM Document
     *
     * @return BpmnDocument
     */
    public function getDomDocument()
    {
        $document = new BpmnDocument($this);
        $document->loadXML($this->bpmn);

        return $document;
    }

    /**
     * Set a value on the properties json column
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setProperty($name, $value)
    {
        $properties = $this->properties;
        $properties[$name] = $value;
        $this->properties = $properties;
    }

    /**
     * Get a value from the properties json column
     *
     * @param string $name
     * @return mixed
     */
    public function getProperty($name)
    {
        return isset($this->properties[$name]) ? $this->properties[$name] : null;
    }

    /**
     * Set the manager id
     *
     * @param int $value
     * @return void
     */
    public function setManagerIdAttribute($value)
    {
        $this->setProperty('manager_id', $value);
    }

    /**
     * Get the the manager id
     *
     * @return int|null
     */
    public function getManagerIdAttribute()
    {
        $property = $this->getProperty('manager_id');

        return collect($property)->get('id', $property);
    }

    /**
     * Get the process manager
     *
     * @return User|null
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the users who can start this process
     */
    public function usersCanCancel()
    {
        $query = $this->morphedByMany(User::class, 'processable')
                      ->wherePivot('method', 'CANCEL');

        return $this instanceof Process
            ? $query->wherePivot('process_version_id', '=', null)
            : $query;
    }

    /**
     * Get the groups who can start this process
     */
    public function groupsCanCancel()
    {
        $query = $this->morphedByMany(Group::class, 'processable')
                      ->wherePivot('method', 'CANCEL');

        return $this instanceof Process
            ? $query->wherePivot('process_version_id', '=', null)
            : $query;
    }

    /**
     * Get the users who can start this process
     */
    public function usersCanEditData()
    {
        $query = $this->morphedByMany(User::class, 'processable')
                      ->wherePivot('method', 'EDIT_DATA');

        return $this instanceof Process
            ? $query->wherePivot('process_version_id', '=', null)
            : $query;
    }

    /**
     * Get the groups who can start this process
     */
    public function groupsCanEditData()
    {
        $query = $this->morphedByMany(Group::class, 'processable')
                      ->wherePivot('method', 'EDIT_DATA');

        return $this instanceof Process
            ? $query->wherePivot('process_version_id', '=', null)
            : $query;
    }

    /**
     * Get the tasks of the process
     *
     * @return array
     */
    public function getTasks()
    {
        $response = [];
        if (empty($this->bpmn)) {
            return $response;
        }
        $definitions = new BpmnDocument($this);
        $definitions->loadXML($this->bpmn);
        $types = [
            'task',
            'userTask',
            'manualTask',
            'scriptTask',
            'serviceTask',
            'callActivity',
        ];
        foreach ($types as $type) {
            $tasks = $definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, $type);
            foreach ($tasks as $task) {
                $response[] = [
                    'id' => $task->getAttribute('id'),
                    'name' => $task->getAttribute('name'),
                    'type' => $task->localName,
                ];
            }
        }

        return $response;
    }
}
