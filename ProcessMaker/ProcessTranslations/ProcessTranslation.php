<?php

namespace ProcessMaker\ProcessTranslations;

use Carbon\Carbon;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Assets\ScreensInProcess;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTranslationToken;
use ProcessMaker\Models\Screen;

class ProcessTranslation
{
    protected $process;

    protected $includeImages;

    protected $humanLanguage = [
        'es' => 'Spanish',
        'de' => 'German',
        'fr' => 'French',
        'ja' => 'Japanese',
        'nl' => 'Dutch',
    ];

    public function __construct(Process $process, $includeImages = false)
    {
        $this->process = $process;
        $this->includeImages = $includeImages;
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
        foreach ($screensInProcess as $screenId) {
            $screen = Screen::find($screenId);
            if ($screen) {
                $nestedScreens = array_merge($nestedScreens, $screen->nestedScreenIds());
            }
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

        $result = [];
        $imgPattern = '/<img[^>]+>/';
        foreach($strings as $string) {
            if ($this->includeImages && preg_match($imgPattern, $string)) {
                $result[] = preg_replace_callback($imgPattern,
                    function ($matches) {
                        $hash = sha1($matches[0]);
                        Cache::put('img.'.$hash, $matches[0], now()->addMinutes(15));
                        return '<img src="' . $hash . '"/>';
                    },
                    $string);
            }
            else {
                $result[] = $string;
            }
        }

        return $result;
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
                            if (array_key_exists('content', $option)) {
                                $elements[] = $option['content'];
                            }
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

        $targetLanguage = array_key_exists($targetLanguage, Languages::ALL) ? $targetLanguage : 'en';

        if (Auth::user() && Auth::user()->username !== '_pm4_anon_user') {
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
                        foreach ($item['config']['options']['optionsList'] as &$option) {
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

            if (isset($pmConfig) && $pmConfig !== '' && array_key_exists('web_entry', $pmConfig) && $pmConfig['web_entry']) {
                $screenIds[] = $pmConfig['web_entry']['screen_id'];
                $screenIds[] = $pmConfig['web_entry']['completed_screen_id'];
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
            if ($batch) {
                $batch->cancel();
            }
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
        foreach ($payload as $screenUuid => $value) {
            $screen = Screen::where('uuid', $screenUuid)->first();
            if ($screen) {
                $screen->translations = $this->generateNewTranslations($value, $screen);
                $screen->save();
            }
        }
    }

    protected function generateNewTranslations($value, $screen)
    {
        $newScreenTranslations = $screen->translations;
        $availableStrings = $this->getStringsInScreen($screen);
        foreach ($value as $languageCode => $translations) {
            $newScreenTranslations[$languageCode]['strings'] = $this->generateNewLanguageTranslations(
                $translations,
                $availableStrings,
                $newScreenTranslations[$languageCode]['strings'] ?? []
            );
            $createdAt = $newScreenTranslations[$languageCode]['created_at'] ?? Carbon::now();
            $newScreenTranslations[$languageCode]['updated_at'] = Carbon::now();
            $newScreenTranslations[$languageCode]['created_at'] = $createdAt;
        }

        return $newScreenTranslations;
    }

    protected function generateNewLanguageTranslations($translations, $availableStrings, $existingTranslations)
    {
        $newTranslations = [];
        foreach ($availableStrings as $availableString) {
            $oldTranslation = $this->getTranslation($availableString, $existingTranslations);
            $inFileTranslation = $this->getTranslation($availableString, $translations);
            if (($oldTranslation !== null && $inFileTranslation === null)
                || ($oldTranslation !== null && $inFileTranslation !== null && $inFileTranslation['string'] === null)) {
                $newTranslations[] = $oldTranslation;
            }
            if ($inFileTranslation !== null
                && ($oldTranslation === null || $oldTranslation !== null && $inFileTranslation !== null)) {
                $newTranslations[] = $inFileTranslation;
            }
        }

        return $newTranslations;
    }

    protected function getTranslation($key, $translationArray)
    {
        foreach ($translationArray as $translation) {
            if ($key === $translation['key']) {
                return $translation;
            }
        }

        return null;
    }
}
