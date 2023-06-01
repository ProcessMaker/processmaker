<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenType;
use ProcessMaker\ProcessTranslations\Languages;
use ProcessMaker\Traits\HasControllerAddons;

class ProcessTranslationController extends Controller
{
    public function export(Process $process, $languageCode)
    {
        $language['language'] = $languageCode;
        $language['humanLanguage'] = Languages::ALL[$languageCode];

        return view('processes.translations.export', compact('process', 'language'));
    }

    public function import(Screen $screen)
    {
        return view('processes.translations.import');
    }
}
