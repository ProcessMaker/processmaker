<?php

namespace ProcessMaker\ProcessTranslations;

use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\MustacheExpressionEvaluator;
use ProcessMaker\Models\Screen;
use ProcessMaker\Package\Translations\Models\Translatable;

class ScreenTranslation extends TranslationManager
{
    private $includeImages = false;

    /**
     * Apply translations to a screen.
     * @param array $screen
     * @return array
     */
    public function applyTranslations($screen)
    {
        $config = $screen['config'];
        $language = $this->getTargetLanguage();
        return $this->searchTranslations($screen['screen_id'], $config, $language);
    }

    /**
     * Evaluate mustache expressions in a screen config.
     * @param array $screenConfig
     * @param array $data
     * @return array
     */
    public function evaluateMustache($screenConfig, $data)
    {
        $mustacheEngine = new MustacheExpressionEvaluator();
        $configEvaluated = $mustacheEngine->render(json_encode($screenConfig), $data);
        return json_decode($configEvaluated, true);
    }

    /**
     * Get the available strings in screens that belongs to a process passed by parameter.
     * @param Process $process
     * @param array $withColumns
     * @return Collection
     */
    public function getStringsByProcess($process, $withColumns = [])
    {
        $screens = $this->getScreens($withColumns, null, $process);
        $screensArr = [];
        foreach ($screens as $screen) {
            $screensArr[] = $this->getStringsByScreen($screen);
        }
        return collect($screensArr)->forget('config');
    }

    /**
     * Get the available strings in screens passed by parameter.
     * @param array $screenIds
     * @param array $withColumns
     * @return Collection
     */
    public function getStringsByScreenIds(array $screenIds, $withColumns = [])
    {
        $screens = $this->getScreens($withColumns, $screenIds, null);
        $screensArr = [];
        foreach ($screens as $screen) {
            $screensArr[] = $this->getStringsByScreen($screen);
        }
        return collect($screensArr)->forget('config');
    }

    /**
     * Get the available strings for a single screen passed by parameter.
     * @param array $screen
     * @return array
     */
    public function getStringsByScreen($screen)
    {
        $screen['availableStrings'] = $this->getStringsInScreen($screen);
        unset($screen['config']);

        return $screen;
    }

    /**
     * Set the include images flag.
     * @param bool $includeImages
     */
    public function setIncludeImages($includeImages)
    {
        $this->includeImages = $includeImages;
    }

    private function getScreens($withColumns, $screenIds = null, $process = null) : SupportCollection
    {
        if (!$screenIds) {
            $screensInProcess = collect($this->getScreenIdsInProcess($process))
                ->unique()
                ->toArray();
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

        $fields = array_merge(['id', 'translations', 'config'], $withColumns);

        $screens = Screen::whereIn('id', $screensInProcess)
            ->get()
            ->map
            ->only($fields)
            ->values();

        return $screens;
    }

    private function getScreenIdsInProcess($process)
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

        foreach (Utils::getElementByMultipleTags($process->getDefinitions(true), $tags) as $element) {
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

        $strings = $this->handleIncludeImages($strings);

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

    private function handleIncludeImages($strings)
    {
        $result = [];
        $imgPattern = '/<img[^>]+>/';
        foreach ($strings as $string) {
            if ($this->includeImages && preg_match($imgPattern, $string)) {
                $result[] = preg_replace_callback($imgPattern,
                    function ($matches) {
                        $hash = sha1($matches[0]);
                        Cache::put('img.' . $hash, $matches[0], now()->addMinutes(15));

                        return '<img src="' . $hash . '"/>';
                    },
                    $string);
            } else {
                $result[] = $string ?? '';
            }
        }

        return $result;
    }

    private function searchTranslations($screenId, $config, $language)
    {
        $translations = null;
        if (class_exists(Translatable::class)) {
            $translations = Translatable::where('translatable_id', $screenId)
                ->where('translatable_type', Screen::class)
                ->where('language_code', $language)->first();
        }

        if (!$translations) {
            return $config;
        }

        foreach ($translations->translations as $key => $translation) {
            if ($translation) {
                $this->applyTranslationsToScreen($key, $translation, $config);
            }
        }

        return $config;
    }

    private function applyTranslationsToScreen($key, $translatedString, &$config)
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
}
