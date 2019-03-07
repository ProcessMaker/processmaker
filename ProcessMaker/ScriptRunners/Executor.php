<?php

namespace ProcessMaker\ScriptRunners;

use ProcessMaker\Exception\ScriptLanguageNotSupported;

class Executor
{
    /**
     * Concrete script runner
     *
     * @var \ProcessMaker\ScriptRunners\Base $runner
     */
    private $runner;

    public function __construct($language)
    {
        $this->runner = $this->getScriptRunnerByLanguage($language);
    }

    /**
     * Run a script code.
     *
     * @param string $code
     * @param array $data
     * @param array $config
     *
     * @return array
     * @throws \RuntimeException
     */
    public function run($code, array $data, array $config)
    {
        return $this->runner->run($code, $data, $config);
    }

    /**
     * Get a runner instance by language
     *
     * @param string $language
     *
     * @return \ProcessMaker\ScriptRunners\Base
     * @throws \ProcessMaker\Exception\ScriptLanguageNotSupported
     */
    private function getScriptRunnerByLanguage($language)
    {
        throw new ScriptLanguageNotSupported($language);
    }
}
