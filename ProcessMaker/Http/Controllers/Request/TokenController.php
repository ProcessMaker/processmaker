<?php
namespace ProcessMaker\Http\Controllers\Request;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Delegation;

class TokenController extends Controller
{

    function openTask($view, Process $process, Application $instance, Delegation $token)
    {
        $instance = $process->getDefinitions()->getEngine()->loadExecutionInstance($instance->uid);
        $dataStore = $instance->getDataStore();
        $data = (object) $dataStore->getData();
        $blade = $token->thread_status === Delegation::THREAD_STATUS_CLOSED ? 'tasks.show' : 'tasks.open';
        return view($blade, compact('process', 'instance', 'token', 'data'));
    }
}
