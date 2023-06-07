<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use function PHPUnit\Framework\isEmpty;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTranslationToken;
use ProcessMaker\ProcessTranslations\Languages;
use ProcessMaker\ProcessTranslations\ProcessTranslation;

class ProcessTranslationController extends Controller
{
    public function index(Request $request)
    {
        $processId = $request->input('process_id', '');
        $filter = $request->input('filter', '');

        $process = Process::findOrFail($processId);

        $processTranslation = new ProcessTranslation($process);
        $screensTranslations = $processTranslation->getProcessScreensWithTranslations();
        $languageList = $processTranslation->getLanguageList($screensTranslations);

        if ($filter != '') {
            $languageList = array_values(collect($languageList)->filter(function ($item) use ($filter) {
                return false !== stristr($item['humanLanguage'], $filter);
            })->toArray());
        }

        foreach ($languageList as $key => &$language) {
            $language['processId'] = $processId;
            // Verify if there are some pending translation for the language and remove it from the list)
            $processTranslationToken = ProcessTranslationToken::where('process_id', $processId)
                ->where('language', $language['language'])
                ->count();
            if ($processTranslationToken) {
                unset($languageList[$key]);
            }
        }

        return response()->json([
            'translatedLanguages' => $languageList,
        ]);
    }

    public function pending(Request $request)
    {
        $processId = $request->input('process_id', '');
        $filter = $request->input('filter', '');

        $processTranslationTokens = ProcessTranslationToken::where('process_id', $processId)->get();

        $translatingLanguages = [];
        foreach ($processTranslationTokens as $processTranslationToken) {
            $batch = Bus::findBatch($processTranslationToken->token);
            $processTranslationToken->humanLanguage = Languages::ALL[$processTranslationToken['language']];
            $processTranslationToken->batch = $batch ? $batch : null;
            $translatingLanguages[] = $processTranslationToken;
        }

        return response()->json([
            'translatingLanguages' => $processTranslationTokens,
        ]);
    }

    public function getAvailableLanguages(Request $request)
    {
        $processId = $request->input('process_id');

        $process = Process::findOrFail($processId);

        $processTranslation = new ProcessTranslation($process);
        $screensTranslations = $processTranslation->getProcessScreensWithTranslations();
        $languageList = $processTranslation->getLanguageList($screensTranslations);

        foreach (Languages::ALL as $key => $value) {
            if (!$this->languageInTranslatedList($key, $languageList)) {
                $availableLanguages[] = [
                    'humanLanguage' => $value,
                    'language' => $key,
                ];
            }
        }

        return response()->json([
            'availableLanguages' => $availableLanguages,
        ]);
    }

    private function languageInTranslatedList($key, $languageList)
    {
        foreach ($languageList as $value) {
            if ($value['language'] === $key) {
                return true;
            }
        }

        return false;
    }

    public function show(Request $request, $processId)
    {
        $process = Process::findOrFail($processId);
        $processTranslation = new ProcessTranslation($process);
        $screensTranslations = $processTranslation->getProcessScreensWithTranslations();

        return response()->json([
            'translations' => $screensTranslations,
        ]);
    }

    public function cancel(Request $request, $processId, $language)
    {
        $process = Process::findOrFail($processId);
        $processTranslation = new ProcessTranslation($process);
        $processTranslation->cancelTranslation($language);

        return response()->json();
    }

    public function delete(Request $request, $processId, $language)
    {
        $process = Process::findOrFail($processId);
        $processTranslation = new ProcessTranslation($process);
        $processTranslation->deleteTranslations($language);

        return response()->json();
    }

    public function update(Request $request)
    {
        $processId = $request->input('process_id');
        $screensTranslations = $request->input('screens_translations');
        $language = $request->input('language');

        $process = Process::findOrFail($processId);

        $processTranslation = new ProcessTranslation($process);
        $processTranslation->updateTranslations($screensTranslations, $language);
    }

    public function export(Request $request, $processId, $languageCode)
    {
        $process = Process::findOrFail($processId);
        $processTranslation = new ProcessTranslation($process);
        $exportList = $processTranslation->exportTranslations($languageCode);

        $fileName = trim($process->name);

        $exportInfo = json_encode([
            'processName' => $process->name,
            'language' => $languageCode,
            'humanLanguage' => Languages::ALL[$languageCode],
        ]);

        return response()->streamDownload(
            function () use ($exportList) {
                echo json_encode($exportList);
            },
            $fileName . '.json',
            [
                'Content-type' => 'application/json',
                'export-info' => $exportInfo,
            ]
        );
    }

    public function preimportValidation(Request $request, $processId)
    {
        $content = $request->file('file')->get();
        $payload = json_decode($content, true);

        $process = Process::findOrFail($processId);
        $processTranslation = new ProcessTranslation($process);
        $importData = $processTranslation->getImportData($payload);

        if (!$importData || !count($importData)) {
            return response(
                ['message' => __('Please verify that the file contains translations for this process.')],
                422
            );
        }

        return [
            'importData' => $importData,
        ];
    }

    public function import(Request $request, $processId)
    {
        $content = $request->file('file')->get();
        $payload = json_decode($content, true);

        $process = Process::findOrFail($processId);
        $processTranslation = new ProcessTranslation($process);
        $processTranslation->importTranslations($payload);

        return response()->json(['processId' => $processId], 200);
    }
}
