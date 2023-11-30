<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTranslationToken;
use ProcessMaker\ProcessTranslations\BatchesJobHandler;
use ProcessMaker\ProcessTranslations\ProcessTranslation;

class OpenAIController extends Controller
{
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
}
