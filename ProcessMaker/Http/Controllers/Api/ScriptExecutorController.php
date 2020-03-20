<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Jobs\BuildScriptExecutor;
use ProcessMaker\Models\ScriptExecutor;

class ScriptExecutorController extends Controller
{
    public function index(Request $request)
    {
        $this->checkAuth($request);
        return new ApiCollection(ScriptExecutor::all());
    }

    public function store(Request $request)
    {
        $this->checkAuth($request);
        ScriptExecutor::create(
            $request->only((new ScriptExecutor)->getFillable())
        );
        return ['status'=>'done'];
    }

    public function update(Request $request, ScriptExecutor $scriptExecutor)
    {
        $this->checkAuth($request);
        $scriptExecutor->update(
            $request->only($scriptExecutor->getFillable())
        );
        
        BuildScriptExecutor::dispatch($scriptExecutor->id, $request->user()->id);

        return ['status'=>'started'];
    }

    private function checkAuth($request)
    {
        if (!$request->user()->is_administrator) {
            throw new AuthorizationException;
        }
    }

    public function cancel(Request $request)
    {
        $pidFile = $request->input('pidFile');
        $pid = file_get_contents($pidFile);
        exec("kill -9 $pid");
        return ['status' => 'canceled', 'pid' => $pid];
    }

    public function availableLanguages()
    {
        $languages = [];
        foreach (config('script-runners') as $key => $config) {
            $languages[] = [
                'value' => $key,
                'text' => $config['name'],
                'initDockerfile' => ScriptExecutor::initDockerfile($key)
            ];
        }
        return ['languages' => $languages];
    }
}
