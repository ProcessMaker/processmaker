<?php

namespace ProcessMaker\ScriptRunners;

class LuaRunner extends Base
{
    /**
     * Configure docker with lua executor
     *
     * @param string $code
     * @param array $dockerConfig
     *
     * @return array
     */
    public function config($code, array $dockerConfig)
    {
        $dockerConfig['image'] = config('script-runners.lua.image');
        $dockerConfig['command'] = 'lua5.3 /opt/executor/bootstrap.lua';
        $dockerConfig['inputs']['/opt/executor/script.lua'] = $code;
        return $dockerConfig;
    }
}
