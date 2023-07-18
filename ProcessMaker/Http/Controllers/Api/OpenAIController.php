<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenAI\Client;
use ProcessMaker\Ai\Handlers\NlqToCategoryHandler;
use ProcessMaker\Ai\Handlers\NlqToPmqlHandler;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\AiSearch;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTranslationToken;
use ProcessMaker\Plugins\Collections\Models\Collection;
use ProcessMaker\ProcessTranslations\BatchesJobHandler;
use ProcessMaker\ProcessTranslations\ProcessTranslation;

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
        $defaultType = $request->input('type');
        $nlqToCategoryHandler = new NlqToCategoryHandler();
        [$type, $classifierUsage, $originalQuestion] = $nlqToCategoryHandler->generatePrompt($defaultType,
            $request->input('question')
        )->execute();

        // Route to the specific prompt
        $nlqToPmqlHandler = new NlqToPmqlHandler();
        [$result, $usage, $originalQuestion] = $nlqToPmqlHandler->generatePrompt(
            $type,
            $originalQuestion
        )->execute();

        // Calc total usage
        $usage->classifierTotalTokens = $classifierUsage->totalTokens;
        $usage->total = $usage->totalTokens + $classifierUsage->totalTokens;

        // If response is json (needed for collections when asking for specific one)
        $resultDecoded = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Search for collection
            if (array_key_exists('collectionName', $resultDecoded)) {
                $collection = Collection::where('name', 'like', '%' . mb_strtolower($resultDecoded['collectionName']) . '%')->first();

                if ($collection) {
                    $resultDecoded['collection'] = $collection;
                } else {
                    $resultDecoded['collectionError'] = _('We could not find a collection that match with the name "' . $resultDecoded['collectionName'] . '". You can search the collection manually and use the following PMQL query: ');
                }

                $result = json_encode($resultDecoded);
            }
        } else {
            $result = json_encode(['pmql' => $result]);
        }

        // Save the response
        $nlqToPmqlHandler->saveResponse($type, $result);

        // Return recent searched
        $recentSearches = AiSearch::where('user_id', Auth::user()->id)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'usage' => $usage,
            'result' => json_decode($result, true),
            'question' => $originalQuestion,
            'lastSearch' => $recentSearches->first(),
            'collection' => isset($collection) ? $collection : null,
            'recentSearches' => $recentSearches,
        ]);
    }

    public function languageTranslation(Request $request)
    {
        // Find process to translate
        $process = Process::findOrFail($request->input('processId'));
        $screenId = $request->input('screenId');
        $option = $request->input('option');

        // Find process screens and translations for each screen
        $processTranslation = new ProcessTranslation($process);
        $columns = ['title', 'description', 'type', 'config'];

        if ($screenId) {
            $screensTranslations = $processTranslation->getScreensWithTranslations($columns, [$screenId]);
        }

        if (!$screenId) {
            $screensTranslations = $processTranslation->getProcessScreensWithTranslations($columns);
        }

        if (!$request->input('manualTranslation')) {
            $processTranslationToken = ProcessTranslationToken::where('process_id', $process->id)->where('language', $request->input('language')['language'])->first();

            if (!$processTranslationToken) {
                $code = uniqid('procress-translation', true);
                $processTranslationToken = new ProcessTranslationToken();
                $processTranslationToken->process_id = $process->id;
                $processTranslationToken->token = $code;
                $processTranslationToken->language = $request->input('language')['language'];
                $processTranslationToken->save();

                $translateProcess = new BatchesJobHandler($process, $screensTranslations, $request->input('language'), $code, Auth::id(), $option);
                $haveStringsToTranslate = $translateProcess->handle();
                if (!$haveStringsToTranslate) {
                    return response()->json([
                        'error' => __('No strings found to translate'),
                    ]);
                }
            } else {
                return response()->json([
                    'error' => 'Already running a translation for this language in background',
                ]);
            }
        }

        return response()->json([
            'processTranslation' => $processTranslation,
            'screensTranslations' => $screensTranslations,
        ]);
    }

    public function recentSearches(Request $request)
    {
        // Return recent searched
        $quantity = $request->input('quantity');
        $recentSearches = AiSearch::where('user_id', Auth::user()->id)
            ->latest()
            ->take($quantity)
            ->get();

        return response()->json([
            'recentSearches' => $recentSearches,
        ]);
    }

    public function deleteRecentSearches(Request $request)
    {
        $deleted = AiSearch::where('user_id', Auth::user()->id)
            ->delete();

        return $deleted;
    }

    // FAKE PR, PLEASE DO NOT MERGE
}
