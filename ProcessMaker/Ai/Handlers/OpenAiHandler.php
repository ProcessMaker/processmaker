<?php

namespace ProcessMaker\Ai\Handlers;

use OpenAI\Client;

abstract class OpenAiHandler
{
    protected $config = [];

    protected $question = '';

    public function __construct() {
    }

    public function getPromptsPath()
    {
        return app_path() . '/Ai/Prompts/';
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setModel(String $model)
    {
        $this->config['model'] = $model;
    }

    public function setMaxTokens(int $maxTokens)
    {
        $this->config['max_token'] = $maxTokens;
    }

    public function setTemperature(float $temperature)
    {
        $this->config['temperature'] = $temperature;
    }

    public function setTopP(float $topP)
    {
        $this->config['top_p'] = $topP;
    }

    public function setN(int $n)
    {
        $this->config['n'] = $n;
    }

    public function setStop(String $stop)
    {
        $this->config['stop'] = $stop;
    }

    public function setFrequencyPenalty(float $frequencyPenalty)
    {
        $this->config['frequency_penalty'] = $frequencyPenalty;
    }

    public function setPresencePenalty(float $presencePenalty)
    {
        $this->config['presence_penalty'] = $presencePenalty;
    }

    abstract public function getPromptFile($type = null);

    abstract public function generatePrompt(String $type = null, String $description);

    abstract public function execute();
}
