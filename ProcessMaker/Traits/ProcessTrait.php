<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Models\User;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessVersion;
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
            $options = ['process' => $this instanceof ProcessVersion ? $this->process : $this];
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
     * Get the users who can start this process
     *
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
     *
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
     *
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
     *
     */
    public function groupsCanEditData()
    {
        $query = $this->morphedByMany(Group::class, 'processable')
                      ->wherePivot('method', 'EDIT_DATA');

        return $this instanceof Process
            ? $query->wherePivot('process_version_id', '=', null)
            : $query;
    }
}
