<?php

namespace ProcessMaker\ProcessTranslations;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Package\Translations\Models\Language;
use ProcessMaker\Package\Translations\Models\Translatable;

class TranslationManager
{
    public static function getTargetLanguage($defaultLanguage = '')
    {
        if ($defaultLanguage) {
            $targetLanguage = $defaultLanguage;
        } else {
            $targetLanguage = self::getBrowserLanguage();
            $targetLanguage = self::getUserLanguage($targetLanguage);
        }

        if (hasPackage('package-translations')) {
            $targetLanguage = self::validateLanguage($targetLanguage);
        }

        return $targetLanguage;
    }

    protected static function getBrowserLanguage()
    {
        return isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
            ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)
            : '';
    }

    protected static function validateLanguage($language)
    {
        $availableLanguages = Language::where('installed', 1)->pluck('code')->toArray();

        return in_array($language, $availableLanguages) ? $language : 'en';
    }

    protected static function getUserLanguage($language)
    {
        if (!Auth::user()->isAnonymous) {
            return Auth::user()->language;
        } elseif (Cache::has('LANGUAGE_ANON_WEBENTRY')) {
            $languageAnon = Cache::get('LANGUAGE_ANON_WEBENTRY');

            return $languageAnon['code'] ?? $language;
        }

        return $language;
    }
}
