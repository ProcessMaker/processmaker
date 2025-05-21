<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Repositories\BpmnDocument;

trait ProcessTrait
{
    /**
     * Parsed process BPMN definitions.
     *
     * @var BpmnDocumentInterface
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

    public function getCounts()
    {
        $result = $this->requests()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $completed = $result->where('status', 'COMPLETED')->first()?->count ?? 0;
        $in_progress = $result->where('status', 'ACTIVE')->first()?->count ?? 0;

        return [
            'completed' => $completed,
            'in_progress' => $in_progress,
            'total' => $completed + $in_progress,
        ];
    }

    /**
     * Get the count of stages per request.
     *
     * This method retrieves the count of stages based on the last_stage_id for each request.
     * If a JSON string of stages is provided, it decodes it; otherwise, it fetches all stages
     * from the database. The method returns an array containing each stage's ID, name, and count.
     *
     * @param string|null $stages A JSON string representing stages. If provided, it will be decoded;
     *                            if null, all stages will be fetched from the database.
     * @return array An array of associative arrays, each containing:
     *               - 'id': The ID of the stage.
     *               - 'name': The name of the stage.
     *               - 'count': The count of occurrences of the stage based on last_stage_id.
     */
    public function getStagesSummary($stages = null)
    {
        if (!empty($stages)) {
            $allStages = $stages;
        } else {
            return [];
        }

        // Assuming 'stages' is a relationship defined in the model
        $result = $this->requests()
            ->selectRaw('last_stage_id, count(*) as count')
            ->groupBy('last_stage_id')
            ->get();

        // Prepare an array to hold the counts for each stage
        $stageCounts = [];
        // Initialize stage counts with zero for all stages
        foreach ($allStages as $stage) {
            $stageCounts[] = [
                'id' => $stage['id'],
                'name' => $stage['name'],
                'count' => 0, // Initialize count to 0 for each stage
                'percentage' => 0, // Initialize percentaje to 0 for each stage
            ];
        }

        foreach ($result as $stage) {
            foreach ($stageCounts as $key => &$countData) {
                if ($countData['id'] == $stage->last_stage_id) {
                    $countData['count'] = $stage->count; // Update the count
                }
            }
        }

        // Calculate the total count of all stages
        $totalCount = array_sum(array_column($stageCounts, 'count'));

        // Calculate the percentage for each stage
        foreach ($stageCounts as &$countData) {
            if ($totalCount > 0) {
                $countData['percentage'] = ($countData['count'] / $totalCount) * 100;
            }
        }

        return $stageCounts;
    }
}
