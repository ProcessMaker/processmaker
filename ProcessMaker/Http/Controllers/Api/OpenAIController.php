<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use OpenAI\Client;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\OpenAI\OpenAIHelper;

class OpenAIController extends Controller
{
    public function NLQToPMQL(Client $client, Request $request)
    {
        $question = $request->input('question');
        $type = $request->input('type');
        $promptFile = '';

        if ($type === 'requests') {
            $promptFile = OpenAIHelper::readPromptFromFile('pmql_code_generator_optimized.md');
        }

        if ($type === 'tasks') {
            $promptFile = OpenAIHelper::readPromptFromFile('pmql_code_generator_optimized_for_tasks.md');
        }

        if ($type != 'requests' && $type != 'tasks') {
            $promptFile = OpenAIHelper::readPromptFromFile('pmql_code_generator.md');
        }

        $config = OpenAIHelper::getNLQToPMQLConfig($promptFile, $question);
        $result = OpenAIHelper::getNLQToPMQL($client, $config);

        return response()->json(['result' => $result]);
    }
}
