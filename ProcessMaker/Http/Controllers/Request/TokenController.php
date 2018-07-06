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
        if ($token->thread_status === Delegation::THREAD_STATUS_CLOSED) {
            return abort(404);
        }
        $instance = $process->getDefinitions()->getEngine()->loadExecutionInstance($instance->uid);
        $dataStore = $instance->getDataStore();
        $startDate = $dataStore->getData('startDate');
        $endDate = $dataStore->getData('endDate');
        $reason = $dataStore->getData('reason');
        $approved = $dataStore->getData('approved');
        return view('nayra.' . $view, compact('process', 'instance', 'token', 'startDate', 'endDate', 'reason', 'approved'));
    }
}
