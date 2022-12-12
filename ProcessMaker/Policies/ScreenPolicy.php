<?php

namespace ProcessMaker\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;

class ScreenPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the screen.
     *
     * @param  \ProcessMaker\Models\User  $user
     * @param  \ProcessMaker\Screen  $screen
     * @return mixed
     */
    public function view(User $user, Screen $screen)
    {
        if ($user->is_administrator) {
            return true;
        }

        $taskId = request()->input('task');
        if (!$taskId) {
            return false;
        }

        $task = ProcessRequestToken::findOrFail($taskId);

        if (!$user->can('update', $task)) {
            return false;
        }

        $taskScreenVersion = $task->getScreenVersion();

        if ($taskScreenVersion->screen_id === $screen->id) {
            return true;
        }

        // Check for nested screen
        $screenFinder = new ScreensInScreen();
        $screenFinder->setProcessRequest($task->processRequest);
        $nestedScreens = $screenFinder->referencesToExport($taskScreenVersion->parent);

        $nestedScreenIds = array_map(function ($screen) {
            return $screen[1];
        }, $nestedScreens);

        if (in_array($screen->id, $nestedScreenIds)) {
            return true;
        }

        return false;
    }
}
