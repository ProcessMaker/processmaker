<?php

namespace ProcessMaker\Ai\Handlers;

use OpenAI\Client;

class NlqToPmqlHandler extends OpenAiHandler
{
    private $config = [
        'model' => 'text-davinci-003',
        'max_tokens' => 1900,
        'temperature' => 0,
        'top_p' => 1,
        'n' => 1,
        'frequency_penalty' => 0,
        'presence_penalty' => 0,
        'stop' => 'END_',
    ];

    private $question = '';

    private function getPromptsPath()
    {
        return app_path() . '/Ai/Prompts/';
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getPrompt($type = null)
    {
        return file_get_contents($this->getPromptsPath() . 'pmql_code_generator_optimized_for_' . $type . '.md');
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

    public function generatePrompt(String $type = null, String $question) : Object
    {
        $this->question = "Question: $question \n";
        $prompt = $this->getPrompt($type);

        $this->config['prompt'] = $prompt . $this->config['stop'] . " \n" . $question . $this->config['stop'] . " \n" . 'Response:' . "\n";

        return $this;
    }

    public function execute()
    {
        $client = app(Client::class);
        $response = $client
            ->completions()
            ->create(array_merge($this->getConfig()));

        return $this->formatResponse($response);
    }

    private function formatResponse($response)
    {
        $result = ltrim($response->choices[0]->text);
        $result = explode('Question:', $result)[0];
        $result = rtrim(rtrim(str_replace("\n", '', $result)));
        $result = str_replace('\'', '', $result);

        return [$result, $response->usage, $this->question];
    }
}
