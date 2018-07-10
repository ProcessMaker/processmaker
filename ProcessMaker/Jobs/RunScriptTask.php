<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;

class RunScriptTask extends TokenAction
{
    /**
     * Execute the script task.
     *
     * @return void
     */
    public function action(TokenInterface $token, ScriptTaskInterface $activity)
    {
        $activity->runScript($token);
    }
}
