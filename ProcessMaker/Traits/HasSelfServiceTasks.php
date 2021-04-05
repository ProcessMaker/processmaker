<?php

namespace ProcessMaker\Traits;

use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Providers\WorkflowServiceProvider;

trait HasSelfServiceTasks
{
    /**
     * Get an updated list of self service tasks from BPMN
     *
     * @return array
     */
    public function getUpdatedSelfServiceTasks()
    {
        $response = [];
        if (empty($this->bpmn)) {
            return $response;
        }
        $definitions = new BpmnDocument();
        $definitions->loadXML($this->bpmn);
        foreach ($definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'task') as $task) {
            $response = $this->assignSelfService($task, $response);
        }

        foreach ($definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'manualTask') as $task) {
            $response = $this->assignSelfService($task, $response);
        }

        foreach ($definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'userTask') as $task) {
            $response = $this->assignSelfService($task, $response);
        }

        return $response;
    }

    /**
    * Assign self service
    */
    private function assignSelfService($task, $response=[]) {
        $id = $task->getAttribute('id');
        $assignment = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment');
        if ($assignment === 'self_service') {
            $response[$id]['groups'] = explode(',', $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedGroups'));
            $response[$id]['users'] = explode(',', $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedUsers'));
        }
        return $response;
    }
}
