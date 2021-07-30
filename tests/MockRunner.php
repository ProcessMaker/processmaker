<?php

namespace Tests;

class MockRunner
{
    public function __construct($_executor)
    {
    }

    public function run($code, $data, $config, $timeout, $user) {
        putenv('HOST_URL=' . config('app.docker_host_url'));
        $res = eval(str_replace('<?php', '', $code));
        return ['output' => $res];
    }
}