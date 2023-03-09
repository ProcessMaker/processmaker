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
        /**
         * Types: requests, tasks, collections, settings, security_logs
         **/
        $type = $request->input('type');
        $question = $request->input('question');
        $model = 'text-davinci-003';

        $prompt = OpenAIHelper::readPromptFromFile('pmql_code_generator_optimized_for_' . $type . '.md');
        $config = OpenAIHelper::getNLQToPMQLConfig($prompt, $question, $model);
        [ $result, $usage ] = OpenAIHelper::runNLQToPMQL($client, $config);

        return response()->json(['result' => $result, 'usage' => $usage]);
    }
}
