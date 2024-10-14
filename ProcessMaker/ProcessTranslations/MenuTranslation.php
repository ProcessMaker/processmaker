<?php

namespace ProcessMaker\ProcessTranslations;

use ProcessMaker\Package\PackageDynamicUI\Models\Menu;
use ProcessMaker\Package\Translations\Models\Translatable;

class MenuTranslation extends Translate
{
    protected $menu;

    public function __construct($menu)
    {
        $this->menu = $menu;
    }

    public function apply()
    {
        $language = $this->getTargetLanguage();
        $translations = $this->getTranslations($language);
        return $this->applyTranslations($translations);
    }
    

    private function getTranslations($language)
    {
        if (!class_exists(Translatable::class)) {
            return null;
        }

        return Translatable::where('translatable_id', $this->menu->id)
            ->where('translatable_type', Menu::class)
            ->where('language_code', $language)
            ->first();
    }

    private function applyTranslations($translations)
    {
        if (!$translations) {
            return $this->menu;
        }

        $links = $this->menu->links;
        foreach ($translations->translations as $key => $translation) {
            if ($translation) {
                $this->updateLinkText($links, $key, $translation);
            }
        }
        $this->menu->links = $links;

        return $this->menu;
    }

    private function updateLinkText(&$links, $key, $translation)
    {
        foreach ($links as &$link) {
            if ($link->text === $key) {
                $link->text = $translation;
                break;
            }
        }
    }
}