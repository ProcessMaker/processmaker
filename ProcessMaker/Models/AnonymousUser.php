<?php

namespace ProcessMaker\Models;

use Illuminate\Contracts\Session\Session;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Providers\WorkflowServiceProvider;

class AnonymousUser extends User
{
    const ANONYMOUS_USERNAME = '_pm4_anon_user';

    protected $table = 'users';

    public $isAnonymous = true;

    public function canEdit(Session $session, ProcessRequestToken $task)
    {
        $node = $task->process->getDefinitions()->findElementById($task->element_id);
        $assignment = $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment');
        if ($assignment === 'requester' || $assignment === 'previous_task_assignee') {
            User::hasRequestIdInSession($session, $task->processRequest);
        }
    }
}