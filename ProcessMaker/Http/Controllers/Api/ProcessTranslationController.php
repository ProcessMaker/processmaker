<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\ProcessTranslations\Languages;
use ProcessMaker\ProcessTranslations\ProcessTranslation;

class ProcessTranslationController extends Controller
{
    public function index(Request $request)
    {
        $processId = $request->input('process_id', '');
        
        $process = Process::findOrFail($processId);
        
        $processTranslation = new ProcessTranslation($process);
        $screensTranslations = $processTranslation->getTranslations();
        $languageList = $processTranslation->getLanguageList($screensTranslations);

        return response()->json([
            'translatedLanguages' => $languageList,
            // 'meta' => $meta,
        ]);
    }

    public function getAvailableLanguages()
    {
        foreach (Languages::ALL as $key => $value) {
            $availableLanguages[] = [
                'humanLanguage' => $value,
                'language' => $key
            ];
        }

        return response()->json([
            'availableLanguages' => $availableLanguages,
        ]);
    }

}
