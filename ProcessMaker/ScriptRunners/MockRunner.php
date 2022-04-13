<?php

namespace ProcessMaker\ScriptRunners;
use Illuminate\Support\Str;
use Mustache_Engine;

class MockRunner
{
    private $executor;

    public function __construct($executor)
    {
        $this->executor = $executor;
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
            $language = $this->executor->language;
            $runnerConfig = config("script-runners.{$language}");
            if ($runnerConfig && isset($runnerConfig['mock_response'])) {
                $res = $runnerConfig['mock_response']($code, $data, $config, $timeout, $user);
            }
        }
        return ['output' => $res];
    }

    public function setTokenId() {}
}