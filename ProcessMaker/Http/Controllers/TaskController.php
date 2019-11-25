<?php

namespace ProcessMaker\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\SearchAutocompleteTrait;

class TaskController extends Controller
{
    use SearchAutocompleteTrait;

    private static $dueLabels = [
        'open' => 'Due',
        'completed' => 'Completed',
        'overdue' => 'Due',
    ];

    public function index()
    {
        $title = 'To Do Tasks';

        if (Request::input('status') == 'CLOSED') {
            $title = 'Completed Tasks';
        }

        return view('tasks.index', compact('title'));
    }

    public function show()
    {
        return view('tasks.show');
    }

    public function edit(ProcessRequestToken $task)
    {
        $this->authorize('update', $task);
        //Mark as unread any not read notification for the task
        Notification::where('data->url', Request::path())
            ->whereNotNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        $definition = $task->getDefinition();
        $screenInterstitial = new Screen();
        $allowInterstitial = false;

        if (array_key_exists('allowInterstitial', $definition)) {
            $allowInterstitial = !!json_decode($definition['allowInterstitial']);
            if (array_key_exists('interstitialScreenRef', $definition) && $definition['interstitialScreenRef']) {
                $screenInterstitial = Screen::find($definition['interstitialScreenRef']);
            } else {
                $screenInterstitial = Screen::where('key', 'interstitial')->first();
            }

        }

        $manager = new ScreenBuilderManager();
        event(new ScreenBuilderStarting($manager, $task->getScreen() ? $task->getScreen()->type : 'FORM'));

        $submitUrl = route('api.tasks.update', $task->id);

        return view('tasks.edit', [
            'task' => $task,
            'dueLabels' => self::$dueLabels,
            'manager' => $manager,
            'allowInterstitial' => $allowInterstitial,
            'screenInterstitial' => $screenInterstitial,
            'submitUrl' => $submitUrl
            ]);
    }
}
