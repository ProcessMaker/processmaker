<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use ProcessMaker\Http\Controllers\Controller;

class ScriptExecutorController extends Controller
{
    public function index(Request $request)
    {
        $this->checkAuth($request);

        $languages = [];
        foreach (config('script-runners') as $key => $config) {
            $userDockerfileContents = '';
            
            $userDockerfilePath = storage_path("docker-build-config/Dockerfile-${key}");
            if (file_exists($userDockerfilePath)) {
                $userDockerfileContents .= file_get_contents($userDockerfilePath);
            }

            $languages[$key] = [
                'userDockerfileContents' => $userDockerfileContents
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
        
        $userDockerfilePath = storage_path("docker-build-config/Dockerfile-${language}");

        file_put_contents($userDockerfilePath, $request->input('userDockerfileContents'));
    }

    private function checkAuth($request)
    {
        if (!$request->user()->is_administrator) {
            throw new AuthorizationException;
        }
    }
}
