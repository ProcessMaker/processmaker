<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Jobs\BuildScriptExecutor;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\Script;

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
        $request->validate(ScriptExecutor::rules());

        $scriptExecutor = ScriptExecutor::create(
            $request->only((new ScriptExecutor)->getFillable())
        );
        
        BuildScriptExecutor::dispatch($scriptExecutor->id, $request->user()->id);
        
        return ['status'=>'started', 'id' => $scriptExecutor->id];
    }

    public function update(Request $request, ScriptExecutor $scriptExecutor)
    {
        $this->checkAuth($request);
        $request->validate(ScriptExecutor::rules());

        $scriptExecutor->update(
            $request->only($scriptExecutor->getFillable())
        );
        
        BuildScriptExecutor::dispatch($scriptExecutor->id, $request->user()->id);

        return ['status'=>'started'];
    }

    public function delete(Request $request, ScriptExecutor $scriptExecutor)
    {
        if ($scriptExecutor->scripts()->count() > 0) {
            throw ValidationException::withMessages(['delete' => __('Can not delete executor when it is assigned to scripts.')]);
        }

        if (ScriptExecutor::where('language', $scriptExecutor->language)->count() === 1) {
            throw ValidationException::withMessages(['delete' => __('Can not delete the only executor for this language.')]);
        }

        $cmd = 'docker images -q ' . $scriptExecutor->dockerImageName(); 
        exec($cmd, $out, $return);
        if (count($out) > 0) {
            $cmd = 'docker rmi ' . $scriptExecutor->dockerImageName(); 
            exec($cmd, $out, $return);

            if ($return !== 0) {
                throw ValidationException::withMessages(['delete' => _("Error removing image.") . " ${cmd} " . implode("\n", $out)]);
            }
        }

        ScriptExecutor::destroy($scriptExecutor->id);
        return ['status' => 'done'];
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
        foreach (Script::scriptFormats() as $key => $config) {
            $languages[] = [
                'value' => $key,
                'text' => $config['name'],
                'initDockerfile' => ScriptExecutor::initDockerfile($key)
            ];
        }
        return ['languages' => $languages];
    }
}
