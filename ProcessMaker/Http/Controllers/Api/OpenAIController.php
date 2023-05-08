<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenAI\Client;
use ProcessMaker\Ai\Handlers\LanguageTranslationHandler;
use ProcessMaker\Ai\Handlers\NlqToCategoryHandler;
use ProcessMaker\Ai\Handlers\NlqToPmqlHandler;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\AiSearch;
use ProcessMaker\Models\Process;
use ProcessMaker\OpenAI\OpenAIHelper;
use ProcessMaker\Plugins\Collections\Models\Collection;
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
        // $languageTranslationHandler = new LanguageTranslationHandler();
        // [$result, $usage, $targetLanguage] = $languageTranslationHandler->generatePrompt(
        //     $request->input('type'),
        //     $request->input('language')
        // )->execute();

        // Find process to translate
        $process = Process::findOrFail($request->input('processId'));

        // Find process screens and translations for each screen
        $processTranslation = new ProcessTranslation($process);
        $screensTranslations = $processTranslation->getTranslations(['title', 'description', 'type']);

        // Translate all strings for all screens
        foreach ($screensTranslations as $screen) {
            // Search all element inside the screen
            // Create an array of labels to translate for all string elements
            // Create an array of strings to translate for textareas
        }

        return response()->json([
            'processTranslation' => $processTranslation,
            'screensTranslations' => $screensTranslations,
        ]);
        // return response()->json([
        //     'result' => $result,
        //     'usage' => $usage,
        //     'targetLanguage' => $targetLanguage,
        // ]);
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
}
