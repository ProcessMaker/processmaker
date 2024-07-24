<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Helpers\MobileHelper;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Bookmark;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessLaunchpad;
use ProcessMaker\Traits\HasControllerAddons;

/**
 * @param Request $request
 * @param Process $process
 *
 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
 */
class ProcessesCatalogueController extends Controller
{
    use HasControllerAddons;

    public function index(Request $request, Process $process = null)
    {
        $manager = app(ScreenBuilderManager::class);
        event(new ScreenBuilderStarting($manager, 'DISPLAY'));
        $launchpad = null;
        $currentUser = Auth::user()->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);
        if (!is_null($process)) {
            $process->launchpad = ProcessLaunchpad::getLaunchpad(true, $process->id);
            $process->bookmark_id = Bookmark::getBookmarked(true, $process->id, $currentUser['id']);
        }

        if (MobileHelper::detectMobile()) {
            $title = __('Process Browser');

            return view('processes-catalogue.mobile', compact('title', 'process', 'currentUser', 'manager'));
        }

        return view('processes-catalogue.index', compact('process', 'currentUser', 'manager'));
    }
}
