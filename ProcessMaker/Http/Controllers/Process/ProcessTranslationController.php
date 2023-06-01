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

    /**
     * Get page import
     *
     * @param Screen $screen
     *
     * @return Factory|View
     */
    public function import(Screen $screen)
    {
        return view('processes.translations.import');
    }

    /**
     * Download the JSON definition of the screen
     *
     * @param Screen $screen
     * @param string $key
     *
     * @return stream
     */
    public function download($processId, $language)
    {
        $fileName = trim($screen->title) . '.json';
        $fileContents = Cache::get($key);

        if (!$fileContents) {
            return abort(404);
        } else {
            return response()->streamDownload(function () use ($fileContents) {
                echo $fileContents;
            }, $fileName, [
                'Content-type' => 'application/json',
            ]);
        }
    }
}
