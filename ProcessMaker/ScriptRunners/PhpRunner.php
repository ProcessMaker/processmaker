<?php

namespace ProcessMaker\ScriptRunners;

class PhpRunner extends Base
{
    /**
     * Configure docker with php executor
     *
     * @param string $code
     * @param array $dockerConfig
     *
     * @return array
     */
    public function config($code, array $dockerConfig)
    {
        $dockerConfig['image'] = config('script-runners.php.image');
        $dockerConfig['command'] = 'php /opt/executor/bootstrap.php';
        $dockerConfig['inputs']['/opt/executor/script.php'] = $code;
        return $dockerConfig;
    }
}
