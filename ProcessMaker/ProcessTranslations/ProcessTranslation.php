<?php

namespace ProcessMaker\ProcessTranslations;

use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection as SupportCollection;
use ProcessMaker\Assets\ScreensInProcess;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Process;
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

    public function getTranslations($withScreenData = [])
    {
        return $this->getScreens($withScreenData);
    }

    private function getScreens($withScreenData) : SupportCollection
    {
        $screensInProcess = collect($this->getScreenIds())->unique();

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
                    $createdAt = $translation['created_at'];
                    $updatedAt = $translation['updated_at'];

                    // If updated is greater than existing in array, modify it with the newest
                    if (array_key_exists($key, $languages)) {
                        $createdAt = $languages[$key]['createdAt'];
                        $updatedAt = $languages[$key]['updatedAt'];
                        if ($languages[$key]['updatedAt'] < $translation['updated_at']) {
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
}
