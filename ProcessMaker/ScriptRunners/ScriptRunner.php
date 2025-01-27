<?php

namespace ProcessMaker\ScriptRunners;

use Illuminate\Contracts\Container\BindingResolutionException;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;

class ScriptRunner
{
    /**
     * Concrete script runner
     *
     * @var Base
     */
    private $runner;

    public function __construct(protected Script $script)
    {
        $this->runner = $this->getScriptRunner($this->script->scriptExecutor);
    }

    /**
     * Run a script code.
     *
     * @param string $code
     * @param array $data
     * @param array $config
     * @param int $timeout
     * @param \ProcessMaker\Models\User $user
     *
     * @return array
     * @throws \RuntimeException
     */
    public function run($code, array $data, array $config, $timeout, $user, $sync, $metadata)
    {
        return $this->runner->run($code, $data, $config, $timeout, $user, $sync, $metadata);
    }

    /**
     * Get a runner instance from executor
     *
     * @param ScriptExecutor $executor
     *
     * @return Base|ScriptMicroserviceRunner|MockRunner
     * @throws ScriptLanguageNotSupported
     * @throws BindingResolutionException
     */
    private function getScriptRunner(ScriptExecutor $executor): Base|ScriptMicroserviceRunner|MockRunner
    {
        if (!config('script-runner-microservice.enabled') || $executor->type === ScriptExecutorType::Custom) {
            $language = strtolower($executor->language);
            $runner = config("script-runners.{$language}.runner");
            if (!$runner) {
                throw new ScriptLanguageNotSupported($language);
            } else {
                $class = "ProcessMaker\\ScriptRunners\\{$runner}";

                return app()->make($class, ['scriptExecutor' => $executor]);
            }
        } else {
            return new ScriptMicroserviceRunner($this->script);
        }
    }

    /**
     * Set the tokenId of reference.
     *
     * @param string $tokenId
     *
     * @return void
     */
    public function setTokenId($tokenId)
    {
        $this->runner->setTokenId($tokenId);
    }
}
