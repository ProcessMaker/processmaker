<?php

namespace ProcessMaker\OpenAI;

use OpenAI\Client;

class OpenAIHelper
{
    const PROMPTS_PATH = __DIR__;

    public static function readPromptFromFile($fileName)
    {
        $promptContent = file_get_contents(self::PROMPTS_PATH . '/Prompts/' . $fileName);

        return $promptContent;
    }

    public static function formatPromptForNPLToPMQL(String $prompt, String $question, String $stop)
    {
        $question = "Question: $question \n";
        $stopSequence = "$stop \n";
        $prompt = "$prompt \n $question $stopSequence Response:";

        return $prompt;
    }

    public static function getNPLToPMQLConfig(String $prompt, String $question)
    {
        $stop = 'END_';
        $prompt = self::formatPromptForNPLToPMQL($prompt, $question, $stop);
        $model = 'code-davinci-002';

        return [
            'prompt' => $prompt,
            'model' => $model,
            'max_tokens' => 1900,
            'temperature' => 0,
            'top_p' => 1,
            'n' => 1,
            'stop' => $stop,
        ];
    }

    public static function getNPLToPMQL(Client $client, $config)
    {
        $result = $client->completions()->create($config);
        $result = ltrim($result->choices[0]->text);
        $result = explode('Question:', $result)[0];
        $result = rtrim(rtrim(str_replace("\n", '', $result)));
        $result = str_replace('\'', '', $result);

        return $result;
    }
}
