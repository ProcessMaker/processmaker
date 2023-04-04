<?php

namespace ProcessMaker\Ai\Handlers;

use OpenAI\Client;

class NlqToPmqlHandler extends OpenAiHandler
{
    public function __construct()
    {
        parent::__construct();
        $this->config = [
            'model' => 'text-davinci-003',
            'max_tokens' => 1900,
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
        return file_get_contents($this->getPromptsPath() . 'nlq_to_pmql_' . $type . '.md');
    }

    public function generatePrompt(String $type = null, String $question) : Object
    {
        $this->question = "Question: $question \n";
        $prompt = $this->getPromptFile($type);
        $prompt = $this->replaceWithCurrentYear($prompt);
        $stopSequence = $this->config['stop'] . " \n";

        $this->config['prompt'] = $prompt . $stopSequence . $this->question . $stopSequence . 'Response:' . "\n";

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

    public function replaceWithCurrentYear($prompt)
    {
        $currentYearReplaced = str_replace('{currentYear}', date('Y'), $prompt);
        $pastYearReplaced = str_replace('{pastYear}', date('Y') - 1, $currentYearReplaced);

        return $pastYearReplaced;
    }
}
