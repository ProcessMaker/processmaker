<?php

namespace ProcessMaker\ProcessTranslations;

use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Models\Screen;
use ProcessMaker\Package\Translations\Models\Translatable;

class ScreenTranslation
{
    protected $screen;

    public function __construct(array $screen)
    {
        $this->screen = $screen;
    }

    public function getStrings($screen) : SupportCollection
    {
        $screen['availableStrings'] = $this->getStringsInScreen($screen);
        unset($screen['config']);

        return collect($screen);
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
        $config = $screen['config'];
        $targetLanguage = '';

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $targetLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }

        $targetLanguage = array_key_exists($targetLanguage, Languages::ALL) ? $targetLanguage : 'en';

        if (!Auth::user()->isAnonymous) {
            $targetLanguage = Auth::user()->language;
        } elseif (Cache::has('LANGUAGE_ANON_WEBENTRY')) {
            $language = Cache::get('LANGUAGE_ANON_WEBENTRY');
            $targetLanguage = $language['code'];
        }

        return $this->searchTranslations($screen['screen_id'], $config, $targetLanguage);
    }

    public function searchTranslations($screenId, $config, $language)
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
}
