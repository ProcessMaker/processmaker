<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Jobs\BuildScriptExecutor;

class ScriptExecutorController extends Controller
{
    public function index(Request $request)
    {
        $this->checkAuth($request);

        $languages = [];
        foreach (config('script-runners') as $key => $config) {
            $appDockerfileContents = '';
            
            $appDockerfilePath = storage_path("docker-build-config/Dockerfile-${key}");
            $mtime = null;
            if (file_exists($appDockerfilePath)) {
                $mtime = filemtime($appDockerfilePath);
                $appDockerfileContents .= file_get_contents($appDockerfilePath);
            }

            $languages[$key] = [
                'mtime' => $mtime,
                'appDockerfileContents' => $appDockerfileContents,
                'initDockerfile' => isset($config['init_dockerfile']) ? $config['init_dockerfile'] : '',
            ];
        }

        return ['languages' => $languages];
    }

    public function update(Request $request, $language)
    {
        $this->checkAuth($request);
        if (!isset(config('script-runners')[$language])) {
            throw new ModelNotFoundException;
        }
        
        $appDockerfilePath = storage_path("docker-build-config/Dockerfile-${language}");
        file_put_contents($appDockerfilePath, $request->input('appDockerfileContents'));

        BuildScriptExecutor::dispatch($language, $request->user()->id);

        return ['status'=>'started'];
    }

    private function checkAuth($request)
    {
        if (!$request->user()->is_administrator) {
            throw new AuthorizationException;
        }
    }
}
