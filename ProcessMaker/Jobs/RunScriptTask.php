<?php
namespace ProcessMaker\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Model\Script;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class RunScriptTask extends TokenAction implements ShouldQueue
{

    /**
     * Execute the script task.
     *
     * @return void
     */
    public function action(TokenInterface $token, ScriptTaskInterface $activity)
    {
        $scriptRef = $activity->getProperty('scriptRef');
        $configuration = json_decode($activity->getProperty('scriptConfiguration'), true);
        Log::info('Script started: ' . $scriptRef);
        $dataStore = $token->getInstance()->getDataStore();
        $data = $dataStore->getData();
        $script = Script::where('uid', $scriptRef)->firstOrFail();
        $response = $script->runScript($data, $configuration);
        if (is_array($response['output'])) {
            foreach ($response['output'] as $key => $value) {
                $dataStore->putData($key, $value);
            }
            $activity->complete($token);
            Log::info('Script completed: ' . $scriptRef);
        } else {
            $token->setStatus(ScriptTaskInterface::TOKEN_STATE_FAILING);
            Log::info('Script failed: ' . $scriptRef . ' - ' . $response['output']);
        }
    }
}
