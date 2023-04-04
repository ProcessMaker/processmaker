<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use OpenAI\Client;
use ProcessMaker\Ai\Handlers\NlqToCategoryHandler;
use ProcessMaker\Ai\Handlers\NlqToPmqlHandler;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\OpenAI\OpenAIHelper;

class OpenAIController extends Controller
{
    public function NLQToPMQL(Client $client, Request $request)
    {
        /**
         * Types: requests, tasks, collections, settings, security_logs
         **/
        $nlqToPmqlHandler = new NlqToPmqlHandler();
        [$result, $usage, $originalQuestion] = $nlqToPmqlHandler->generatePrompt(
            $request->input('type'),
            $request->input('question')
        )->execute();

        return response()->json([
            'result' => $result,
            'usage' => $usage,
            'question' => $originalQuestion,
        ]);
    }

    public function NLQToCategory(Client $client, Request $request)
    {
        $nlqToCategoryHandler = new NlqToCategoryHandler();
        [$result, $usage, $originalQuestion] = $nlqToCategoryHandler->generatePrompt(null,
            $request->input('question')
        )->execute();

        return response()->json([
            'result' => $result,
            'usage' => $usage,
            'question' => $originalQuestion,
        ]);
    }
}
