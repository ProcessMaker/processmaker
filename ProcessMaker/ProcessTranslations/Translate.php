<?php

namespace ProcessMaker\ProcessTranslations;

use Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Package\PackageDynamicUI\Models\Menu;
use ProcessMaker\Package\Translations\Models\Translatable;
use ProcessMaker\Package\Translations\Models\Language;

class Translate
{
    public function getTargetLanguage()
    {
        $targetLanguage = $this->getBrowserLanguage();
        $targetLanguage = $this->validateLanguage($targetLanguage);
        $targetLanguage = $this->getUserLanguage($targetLanguage);
        
        return $targetLanguage;
    }

    protected function getBrowserLanguage()
    {
        return isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) 
            ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) 
            : '';
    }

    protected function validateLanguage($language)
    {
        $availableLanguages = Language::where('installed', 1)->pluck('code')->toArray();
        return in_array($language, $availableLanguages) ? $language : 'en';
    }

    protected function getUserLanguage($language)
    {
        if (!Auth::user()->isAnonymous) {
            return Auth::user()->language;
        } else if (Cache::has('LANGUAGE_ANON_WEBENTRY')) {
            return Cache::get('LANGUAGE_ANON_WEBENTRY');
        }
        
        return $language;
    }

}