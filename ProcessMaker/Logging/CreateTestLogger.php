<?php

namespace ProcessMaker\Logging;

use Monolog\Handler\TestHandler;
use Monolog\Logger;

class CreateTestLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $monolog = new Logger('test');
        $monolog->pushHandler(new TestHandler());

        return $monolog;
    }
}
