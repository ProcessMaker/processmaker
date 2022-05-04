<?php

namespace ProcessMaker\ScriptRunners;

class PhpInlineRunner
{
    public function __construct($_executor = null)
    {
    }

    public function run($code, $data, $config, $timeout, $user) {
        $res = eval(str_replace('<?php', '', $code));
        return ['output' => $res];
    }

    public function setTokenId() {}
}