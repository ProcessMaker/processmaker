<?php

namespace ProcessMaker\Ai\Handlers;

use OpenAI\Client;

class NlqToCategoryHandler extends OpenAiHandler
{
    public function __construct()
    {
        parent::__construct();
        $this->config = [
            'model' => 'text-davinci-003',
            'max_tokens' => 20,
            'temperature' => 0,
            'top_p' => 1,
            'n' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'stop' => 'END_',
        ];
    }

    public function getPromptFile($type = null)
    {
        return file_get_contents($this->getPromptsPath() . 'nlq_to_category.md');
    }

    public function generatePrompt(String $type = null, String $question) : Object
    {
        $this->question = $question;
        $prompt = $this->getPromptFile($type);
        $prompt = $this->replaceQuestion($prompt, $question);
        $prompt = $this->replaceStopSequence($prompt);

        $this->config['prompt'] = $prompt;

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

        return [$result, $response->usage, $this->question];
    }

    public function replaceStopSequence($prompt)
    {
        $replaced = str_replace('{stopSequence}', $this->config['stop'] . " \n", $prompt);
        return $replaced;
    }

    public function replaceQuestion($prompt, $question)
    {
        $replaced = str_replace('{question}', $question . " \n", $prompt);
        return $replaced;
    }

    public function replaceWithCurrentYear($prompt)
    {
        $currentYearReplaced = str_replace('{currentYear}', date('Y'), $prompt);
        $pastYearReplaced = str_replace('{pastYear}', date('Y') - 1, $currentYearReplaced);

        return $pastYearReplaced;
    }
}
