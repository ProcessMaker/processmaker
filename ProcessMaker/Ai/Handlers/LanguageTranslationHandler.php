<?php

namespace ProcessMaker\Ai\Handlers;

use Illuminate\Support\Facades\Auth;
use OpenAI\Client;
use ProcessMaker\Events\ProcessTranslationChunkEvent;

class LanguageTranslationHandler extends OpenAiHandler
{
    protected $targetLanguage = 'Spanish';

    protected $processId = null;

    public function __construct()
    {
        parent::__construct();
        $this->config = [
            'model' => 'text-davinci-003',
            'max_tokens' => 2200,
            'temperature' => 0,
            'top_p' => 1,
            'n' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'stop' => 'END_',
        ];
    }

    public function setTargetLanguage($language)
    {
        $this->targetLanguage = $language;
    }

    public function setProcessId($processId)
    {
        $this->processId = $processId;
    }

    public function getPromptFile($type = null)
    {
        return file_get_contents($this->getPromptsPath() . 'language_translation_' . $type . '.md');
    }

    public function generatePrompt(String $type = null, String $json_list) : Object
    {
        $this->$json_list = $json_list;
        $prompt = $this->getPromptFile($type);
        $prompt = $this->replaceJsonList($prompt, $json_list);
        $prompt = $this->replaceLanguage($prompt, $this->targetLanguage['humanLanguage']);
        $prompt = $this->replaceStopSequence($prompt);
        $this->config['prompt'] = $prompt;

        return $this;
    }

    public function execute()
    {
        $client = app(Client::class);
        $stream = $client
            ->completions()
            ->createStreamed(array_merge($this->getConfig()));

        $fullResponse = '';
        foreach ($stream as $response) {
            self::sendResponse($response->choices[0]->text);
            $fullResponse .= $response->choices[0]->text;
        }

        \Log::info($fullResponse);

        return $this->formatResponse($fullResponse);
    }

    private function formatResponse($response)
    {
        $result = ltrim($response);
        $result = rtrim(rtrim(str_replace("\n", '', $result)));
        $result = str_replace('\'', '', $result);

        return [$result, 0, $this->question];
    }

    public function replaceStopSequence($prompt)
    {
        $replaced = str_replace('{stopSequence}', $this->config['stop'] . " \n", $prompt);

        return $replaced;
    }

    public function replaceJsonList($prompt, $json_list)
    {
        $replaced = str_replace('{json_list}', $json_list . " \n", $prompt);

        return $replaced;
    }

    public function replaceLanguage($prompt, $language)
    {
        $replaced = str_replace('{language}', $language . " \n", $prompt);

        return $replaced;
    }

    private function sendResponse($response)
    {
        if ($this->processId) {
            event(new ProcessTranslationChunkEvent($this->processId, $this->targetLanguage['language'], $response));
        }
    }
}
