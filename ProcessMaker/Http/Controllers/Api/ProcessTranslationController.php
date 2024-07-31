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

        $processVersion = Process::find($processId)->getDraftOrPublishedLatestVersion();

        $processTranslation = new ProcessTranslation($processVersion);
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

        $languageList = collect($languageList)->sortBy('humanLanguage');

        return response()->json([
            'translatedLanguages' => array_values($languageList->toArray()),
            'permissions' => [
                'create'   => $request->user()->can('create-process-translations'),
                'view' => $request->user()->can('view-process-translations'),
                'edit'   => $request->user()->can('edit-process-translations'),
                'delete' => $request->user()->can('delete-process-translations'),
                'cancel' => $request->user()->can('cancel-process-translations'),
                'import' => $request->user()->can('import-process-translations'),
                'export' => $request->user()->can('export-process-translations'),
            ],
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
            if ($batch) {
                $processTranslationToken->batch = $batch;
                $translatingLanguages[] = $processTranslationToken;
            }
        }

        $processTranslationTokens = collect($processTranslationTokens)->sortBy('humanLanguage');

        return response()->json([
            'translatingLanguages' => array_values($processTranslationTokens->toArray()),
        ]);
    }

    public function getAvailableLanguages(Request $request)
    {
        $processId = $request->input('process_id');

        $processVersion = Process::find($processId)->getDraftOrPublishedLatestVersion();

        $processTranslation = new ProcessTranslation($processVersion);
        $screensTranslations = $processTranslation->getProcessScreensWithTranslations();
        $translatedLanguageList = $processTranslation->getLanguageList($screensTranslations);
        $translatingLanguageList = ProcessTranslationToken::where('process_id', $processId)->get();

        $availableLanguages = [];

        foreach (Languages::ALL as $key => $value) {
            if (
                !$this->languageInTranslatedList($key, $translatedLanguageList)
                && !$this->languageInTranslatingList($key, $translatingLanguageList)
            ) {
                $availableLanguages[] = [
                    'humanLanguage' => $value,
                    'language' => $key,
                ];
            }
        }

        // Available Pm Languages (user settings)
        $pmLangs = [];
        foreach (scandir(app()->langPath()) as $file) {
            preg_match('/([a-z]{2})\\.json/', $file, $matches);
            if (!empty($matches)) {
                $pmLangs[] = $matches[1];
            }
        }

        // Our form controls need attribute:value pairs sot we convert the langs array to and associative one
        $availablePmLanguages = array_combine($pmLangs, $pmLangs);

        return response()->json([
            'availableLanguages' => $availableLanguages,
            'availablePmLanguages' => $availablePmLanguages,
        ]);
    }

    private function languageInTranslatedList($key, $translatedLanguageList)
    {
        foreach ($translatedLanguageList as $value) {
            if ($value['language'] === $key) {
                return true;
            }
        }

        return false;
    }

    private function languageInTranslatingList($key, $pendingLanguageList)
    {
        foreach ($pendingLanguageList as $value) {
            if ($value['language'] === $key) {
                return true;
            }
        }

        return false;
    }

    public function show(Request $request, $processId)
    {
        $processVersion = Process::find($processId)->getDraftOrPublishedLatestVersion();
        $processTranslation = new ProcessTranslation($processVersion);
        $screensTranslations = $processTranslation->getProcessScreensWithTranslations();

        return response()->json([
            'translations' => $screensTranslations,
        ]);
    }

    public function cancel(Request $request, $processId, $language)
    {
        $processVersion = Process::find($processId)->getDraftOrPublishedLatestVersion();
        $processTranslation = new ProcessTranslation($processVersion);
        $processTranslation->cancelTranslation($language);

        return response()->json();
    }

    public function delete(Request $request, $processId, $language)
    {
        $processVersion = Process::find($processId)->getDraftOrPublishedLatestVersion();
        $processTranslation = new ProcessTranslation($processVersion);
        $processTranslation->deleteTranslations($language);

        return response()->json();
    }

    public function update(Request $request)
    {
        $processId = $request->input('process_id');
        $screensTranslations = $request->input('screens_translations');
        $language = $request->input('language');

        $processVersion = Process::find($processId)->getDraftOrPublishedLatestVersion();
        $processTranslation = new ProcessTranslation($processVersion);
        $processTranslation->updateTranslations($screensTranslations, $language);
    }

    public function export(Request $request, $processId, $languageCode)
    {
        $processVersion = Process::find($processId)->getDraftOrPublishedLatestVersion();
        $processTranslation = new ProcessTranslation($processVersion);
        $exportList = $processTranslation->exportTranslations($languageCode);

        $fileName = trim($processVersion->name);

        $exportInfo = json_encode([
            'processName' => $processVersion->name,
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

        $processVersion = Process::find($processId)->getDraftOrPublishedLatestVersion();
        $processTranslation = new ProcessTranslation($processVersion);
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

        $processVersion = Process::find($processId)->getDraftOrPublishedLatestVersion();
        $processTranslation = new ProcessTranslation($processVersion);
        $processTranslation->importTranslations($payload);

        return response()->json(['processId' => $processId], 200);
    }
}
