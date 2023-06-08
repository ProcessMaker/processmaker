<?php

namespace ProcessMaker\ProcessTranslations;

use Carbon\Carbon;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use ProcessMaker\Assets\ScreensInProcess;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTranslationToken;
use ProcessMaker\Models\Screen;

class ProcessTranslation
{
    protected $process;

    protected $humanLanguage = [
        'es' => 'Spanish',
        'de' => 'German',
        'fr' => 'French',
        'ja' => 'Japanese',
        'nl' => 'Dutch',
    ];

    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    public function getProcessScreensWithTranslations($withScreenData = [])
    {
        return $this->getScreens($withScreenData, null);
    }

    public function getScreensWithTranslations($withScreenData, array $screenIds)
    {
        return $this->getScreens($withScreenData, $screenIds);
    }

    private function getScreens($withScreenData, $screenIds = null) : SupportCollection
    {
        if (!$screenIds) {
            $screensInProcess = collect($this->getScreenIds())->unique()->toArray();
        } else {
            $screensInProcess = $screenIds;
        }

        $nestedScreens = [];
        foreach ($screensInProcess as $screen) {
            $nestedScreens = array_merge($nestedScreens, Screen::findOrFail($screen)->nestedScreenIds());
        }

        $screensInProcess = collect(array_merge($screensInProcess, $nestedScreens))->unique();

        $fields = array_merge(['id', 'translations', 'config'], $withScreenData);

        $screens = Screen::whereIn('id', $screensInProcess)
            ->get()
            ->map
            ->only($fields)
            ->values();

        return $this->getStrings($screens);
    }

    public function getStrings($screens) : SupportCollection
    {
        $screensArr = [];
        foreach ($screens as $screen) {
            $screen['availableStrings'] = $this->getStringsInScreen($screen);
            unset($screen['config']);
            $screensArr[] = $screen;
        }

        return collect($screensArr)->forget('config');
    }

    public function getLanguageList($screensTranslations)
    {
        return $this->getTranslatedLanguageList($screensTranslations);
    }

    public function getTranslatedLanguageList($screensTranslations)
    {
        $languages = [];

        foreach ($screensTranslations as $screenTranslation) {
            if ($screenTranslation['translations']) {
                foreach ($screenTranslation['translations'] as $key => $translation) {
                    if (array_key_exists('created_at', $translation)) {
                        $createdAt = $translation['created_at'];
                    }

                    if (array_key_exists('updated_at', $translation)) {
                        $updatedAt = $translation['updated_at'];
                    }

                    // If updated is greater than existing in array, modify it with the newest
                    if (array_key_exists($key, $languages)) {
                        $createdAt = $languages[$key]['createdAt'];
                        $updatedAt = $languages[$key]['updatedAt'];

                        if (array_key_exists('updated_at', $translation) && $languages[$key]['updatedAt'] < $translation['updated_at']) {
                            $createdAt = $translation['created_at'];
                            $updatedAt = $translation['updated_at'];
                        }
                    }

                    // Add, Update languages array
                    $languages[$key] = [
                        'language' => $key,
                        'humanLanguage' => Languages::ALL[$key],
                        'createdAt' => $createdAt,
                        'updatedAt' => $updatedAt,
                    ];
                }
            }
        }

        return array_values($languages);
    }

    private function getStringsInScreen($screen)
    {
        $strings = [];
        if (!$screen) {
            return $strings;
        }

        $config = $screen['config'];

        if ($config) {
            foreach ($config as $page) {
                if (isset($page['items']) && is_array($page['items'])) {
                    $strings = array_merge($strings, self::getStringElements($page['items']));
                }
            }
        }

        return $strings;
    }

    private static function getStringElements($items, $parent = null)
    {
        $elements = [];

        foreach ($items as $item) {
            if (isset($item['items']) && is_array($item['items'])) {
                // If have items and is a loop ..
                if ($item['component'] == 'FormLoop') {
                    $elements = array_merge($elements, self::getStringElements($item['items']));
                }
                // If have items and is a table ..
                if ($item['component'] == 'FormMultiColumn') {
                    foreach ($item['items'] as $cell) {
                        if (is_array($cell)) {
                            $elements = array_merge($elements, self::getStringElements($cell));
                        }
                    }
                }
            } else {
                if (!isset($item['component'])) {
                    continue;
                }

                if ($item['component'] === 'FormNestedScreen') {
                    continue;
                }

                if ($item['component'] === 'FormImage') {
                    continue;
                }

                // Specific for Rich text
                if ($item['component'] === 'FormHtmlViewer') {
                    $elements[] = $item['config']['content'];
                }

                // Specific for Select list
                if ($item['component'] === 'FormSelectList') {
                    if (isset($item['config']) && isset($item['config']['options']) && isset($item['config']['options']['optionsList'])) {
                        foreach ($item['config']['options']['optionsList'] as $option) {
                            $elements[] = $option['content'];
                        }
                    }
                }

                // Look for label strings
                if (isset($item['config']) && isset($item['config']['label'])) {
                    $elements[] = $item['config']['label'];
                }

                // Look for helper strings
                if (isset($item['config']) && isset($item['config']['helper'])) {
                    $elements[] = $item['config']['helper'];
                }

                // Look for placeholder strings
                if (isset($item['config']) && isset($item['config']['placeholder'])) {
                    $elements[] = $item['config']['placeholder'];
                }
            }
        }

        return $elements;
    }

    public function applyTranslations($screen)
    {
        if (!$screen) {
            return;
        }
        $config = $screen['config'];
        $translations = $screen['translations'];
        $targetLanguage = '';

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $targetLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }

        $targetLanguage = in_array($targetLanguage, Languages::ALL) ? $targetLanguage : 'en';

        if (Auth::user()) {
            $targetLanguage = Auth::user()->language;
        }

        if (!$translations) {
            return $config;
        }

        if (array_key_exists($targetLanguage, $translations)) {
            foreach ($translations[$targetLanguage]['strings'] as $translation) {
                $this->applyTranslationsToScreen($translation['key'], $translation['string'], $config);
            }
        }

        return $config;
    }

    public function applyTranslationsToScreen($key, $translatedString, &$config)
    {
        if ($config) {
            foreach ($config as &$page) {
                if (isset($page['items']) && is_array($page['items'])) {
                    $replaced = self::applyTranslationToElement($page['items'], $key, $translatedString);
                }
            }
        }

        return $config;
    }

    private static function applyTranslationToElement(&$items, $key, $translatedString)
    {
        foreach ($items as &$item) {
            if (isset($item['items']) && is_array($item['items'])) {
                // If have items and is a loop ..
                if ($item['component'] == 'FormLoop') {
                    $replaced = self::applyTranslationToElement($item['items'], $key, $translatedString);
                }
                // If have items and is a table ..
                if ($item['component'] == 'FormMultiColumn') {
                    foreach ($item['items'] as &$cell) {
                        if (is_array($cell)) {
                            $replaced = self::applyTranslationToElement($cell, $key, $translatedString);
                        }
                    }
                }
            } else {
                if (!isset($item['component'])) {
                    continue;
                }

                if ($item['component'] === 'FormNestedScreen') {
                    continue;
                }

                if ($item['component'] === 'FormImage') {
                    continue;
                }

                // Specific for Rich text
                if ($item['component'] === 'FormHtmlViewer' && $item['config']['content'] === $key) {
                    $item['config']['content'] = $translatedString;
                }

                // Specific for Select list
                if ($item['component'] === 'FormSelectList') {
                    if (isset($item['config']) && isset($item['config']['options']) && isset($item['config']['options']['optionsList'])) {
                        foreach ($item['config']['options']['optionsList'] as $option) {
                            if ($option['content'] === $key) {
                                $option['content'] = $translatedString;
                            }
                        }
                    }
                }

                // Look for label strings
                if (isset($item['config']) && isset($item['config']['label']) && $item['config']['label'] === $key) {
                    $item['config']['label'] = $translatedString;
                }

                // Look for helper strings
                if (isset($item['config']) && isset($item['config']['helper']) && $item['config']['helper'] === $key) {
                    $item['config']['helper'] = $translatedString;
                }

                // Look for placeholder strings
                if (isset($item['config']) && isset($item['config']['placeholder']) && $item['config']['placeholder'] === $key) {
                    $item['config']['placeholder'] = $translatedString;
                }
            }
        }

        return $items;
    }

    private function getScreenIds()
    {
        $tags = [
            'bpmn:task',
            'bpmn:manualTask',
            'bpmn:startEvent',
            'bpmn:endEvent',
            'bpmn:serviceTask',
            'bpmn:callActivity',
        ];

        $screenIds = [];

        foreach (Utils::getElementByMultipleTags($this->process->getDefinitions(true), $tags) as $element) {
            $screenId = $element->getAttribute('pm:screenRef');
            $interstitialScreenId = $element->getAttribute('pm:interstitialScreenRef');
            $allowInterstitial = $element->getAttribute('pm:allowInterstitial');
            $pmConfig = $element->getAttribute('pm:config');

            if ($pmConfig) {
                $pmConfig = json_decode($pmConfig, true);
            }

            if (is_numeric($screenId)) {
                $screenIds[] = $screenId;
            }

            // Let's check if interstitialScreen exist
            if (is_numeric($interstitialScreenId) && $allowInterstitial === 'true') {
                $screenIds[] = $interstitialScreenId;
            }

            if (isset($pmConfig) && $pmConfig !== '' && array_key_exists('screenRef', $pmConfig) && is_numeric($pmConfig['screenRef'])) {
                $screenIds[] = $pmConfig['screenRef'];
            }
        }

        return $screenIds;
    }

    public function deleteTranslations($language)
    {
        $screensTranslations = $this->getProcessScreensWithTranslations();

        $translations = null;
        foreach ($screensTranslations as $screenTranslation) {
            if ($screenTranslation['translations']) {
                $translations = $screenTranslation['translations'];

                if (array_key_exists($language, $translations)) {
                    unset($translations[$language]);
                }
            }
            $screen = Screen::findOrFail($screenTranslation['id']);

            $screen->translations = $translations;
            $screen->save();
        }

        // Remove pending tokens
        $processTranslationToken = ProcessTranslationToken::where('process_id', $this->process->id)
            ->where('language', $language)
            ->first();

        if ($processTranslationToken) {
            $token = $processTranslationToken->token;
            $processTranslationToken->delete();

            // Cancel pending batch jobs
            $batch = Bus::findBatch($token);
            $batch->cancel();
        }

        return true;
    }

    public function cancelTranslation($language)
    {
        // Remove pending token
        $processTranslationToken = ProcessTranslationToken::where('process_id', $this->process->id)
            ->where('language', $language)
            ->first();

        if ($processTranslationToken) {
            $token = $processTranslationToken->token;
            $processTranslationToken->delete();

            // Cancel pending batch jobs
            $batch = Bus::findBatch($token);
            $batch->cancel();
        }

        return true;
    }

    public function updateTranslations($screenTranslations, $language)
    {
        foreach ($screenTranslations as $screenTranslation) {
            $screen = Screen::findOrFail($screenTranslation['id']);
            $translations = $screen->translations;

            if (!$screenTranslation['translations'] || !array_key_exists($language, $screenTranslation['translations'])) {
                $screenTranslation['translations'][$language]['strings'] = [];
            }

            foreach ($screenTranslation['translations'] as $key => $value) {
                if ($key === $language) {
                    unset($translations[$key]);
                    $screenTranslation['translations'][$key] = $value;
                    $screenTranslation['translations'][$key]['updated_at'] = Carbon::now();
                    if (!array_key_exists('created_at', $screenTranslation['translations'][$key])) {
                        $screenTranslation['translations'][$key]['created_at'] = $screenTranslation['translations'][$key]['updated_at'];
                    }
                    $translations[$key] = $screenTranslation['translations'][$key];
                }
            }

            $screen->translations = $translations;
            $screen->save();
        }
    }

    public function exportTranslations($language)
    {
        $screensTranslations = $this->getProcessScreensWithTranslations();

        $translations = null;
        $exportList = [];
        foreach ($screensTranslations as $screenTranslation) {
            $screen = Screen::findOrFail($screenTranslation['id']);
            $uuid = $screen->uuid;

            $availableStrings = $screenTranslation['availableStrings'];

            if ($screenTranslation['translations']) {
                $translations = $screenTranslation['translations'];
            }

            foreach ($availableStrings as $availableString) {
                $translation = ['key' => $availableString, 'string' => ''];
                if (array_key_exists($language, $translations) && array_key_exists('strings', $translations[$language])) {
                    foreach ($translations[$language]['strings'] as $item) {
                        if ($availableString === $item['key']) {
                            $translation['string'] = $item['string'];
                        }
                    }
                }
                $exportList[$uuid][$language][] = $translation;
            }
        }

        // Generate json file to export
        return $exportList;
    }

    public function getImportData($payload)
    {
        $screens = [];
        foreach ($payload as $screenId => $value) {
            $languages = [];
            $screen = Screen::where('uuid', $screenId)->first();
            if ($screen) {
                foreach ($value as $languageCode => $translations) {
                    $languages[] = [
                        'language' => $languageCode,
                        'languageHuman' => Languages::ALL[$languageCode],
                    ];
                }
                $screens[] = [
                    'id' => $screen->id,
                    'uuid' => $screenId,
                    'title' => $screen->title,
                    'languages' => $languages,
                ];
            }
        }

        // Group by language
        $languageGrouped = [];
        foreach ($screens as $uuid => $value) {
            foreach ($value['languages'] as $key => $translations) {
                $languageGrouped[$translations['language']]['languageHuman'] = $translations['languageHuman'];
                $languageGrouped[$translations['language']]['screens'][$value['uuid']] = $value['title'];
            }
        }

        return $languageGrouped;
    }

    public function importTranslations($payload)
    {
        $screens = [];
        foreach ($payload as $screenUuid => $value) {
            $screen = Screen::where('uuid', $screenUuid)->first();
            $newScreenTranslations = [];
            if ($screen) {
                $screenTranslations = $screen->translations;
                $availableStrings = $this->getStringsInScreen($screen);

                foreach ($value as $languageCode => $translations) {
                    if (!array_key_exists($languageCode, $screenTranslations)) {
                        $screenTranslations[$languageCode]['strings'] = [];
                    }

                    $newTranslations = [];
                    // For each of the available elements in the screens
                    foreach ($availableStrings as $availableString) {
                        // We need to check if there are current translations in the translation
                        // column (old translations) for that available string
                        $foundOld = false;
                        foreach ($screenTranslations[$languageCode]['strings'] as $inDbTranslation) {
                            if ($availableString === $inDbTranslation['key']) {
                                $foundOld = true;
                                $oldTranslation = [
                                    'key' => $inDbTranslation['key'],
                                    'string' => $inDbTranslation['string'],
                                ];
                            }
                        }

                        // We need to check if there are some translation in the translation file
                        // for that available string
                        $foundInFile = false;
                        foreach ($translations as $importedTranslation) {
                            if ($availableString === $importedTranslation['key']) {
                                $foundInFile = true;
                                $inFileTranslation = [
                                    'key' => $importedTranslation['key'],
                                    'string' => $importedTranslation['string'],
                                ];
                            }
                        }

                        if ($foundOld && !$foundInFile) {
                            $newTranslations[] = $oldTranslation;
                        }

                        if (!$foundOld && $foundInFile) {
                            $newTranslations[] = $inFileTranslation;
                        }

                        if ($foundOld && $foundInFile && $inFileTranslation['string'] === '') {
                            $newTranslations[] = $inFileTranslation;
                        }

                        if ($foundOld && $foundInFile && $inFileTranslation['string'] === null) {
                            $newTranslations[] = $oldTranslation;
                        }

                        if ($foundOld && $foundInFile && $inFileTranslation['string'] && $inFileTranslation['string'] !== '') {
                            $newTranslations[] = $inFileTranslation;
                        }
                    }

                    // Assign new translations to language in screen
                    // $newScreenTranslations[$languageCode]['strings'] = $newTranslations;
                    foreach ($screenTranslations as $language => $translations) {
                        if ($language === $languageCode) {
                            $screenTranslations[$languageCode]['strings'] = $newTranslations;
                        }
                    }
                }
                $screen->translations = $screenTranslations;
                $screen->save();
            }
        }
    }
}
