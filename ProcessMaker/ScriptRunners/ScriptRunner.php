<?php

namespace ProcessMaker\ScriptRunners;

use ProcessMaker\Exception\ScriptLanguageNotSupported;

class ScriptRunner
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
     * @param integer $timeout
     * @param \ProcessMaker\Models\User $user
     *
     * @return array
     * @throws \RuntimeException
     */
    public function run($code, array $data, array $config, $timeout = 60, $user)
    {
        // throw new \Exception("HERE " . $user->id);
        return $this->runner->run($code, $data, $config, $timeout, $user);
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
        $language = strtolower($language);
        $runner = config("script-runners.{$language}.runner");
        if (!$runner) {
            throw new ScriptLanguageNotSupported($language);
        } else {
            $class = "ProcessMaker\\ScriptRunners\\{$runner}";
            return new $class;
        }
    }
}
