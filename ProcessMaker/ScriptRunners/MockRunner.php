<?php

namespace ProcessMaker\ScriptRunners;
use Illuminate\Support\Str;

class MockRunner
{
    public function __construct($_executor)
    {
    }

    public function run($code, $data, $config, $timeout, $user) {
        if (app()->env !== 'testing') {
            throw new \Exception("MockRunner is for tests only.");
        }
        putenv('HOST_URL=' . config('app.docker_host_url'));
        if (Str::startsWith($code, '<?php')) {
            $res = eval(str_replace('<?php', '', $code));
        } else {
            $res = ['response' => 1];
        }
        return ['output' => $res];
    }

    public function setTokenId() {}
}