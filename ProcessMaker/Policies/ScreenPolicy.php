<?php

namespace ProcessMaker\Policies;

use ProcessMaker\Models\User;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ProcessRequestToken;
use Illuminate\Auth\Access\HandlesAuthorization;
use ProcessMaker\Assets\ScreensInScreen;

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

        $taskScreen = $task->getScreen();
        
        if ($taskScreen->id === $screen->id) {
            return true;
        }

        // Check for nested screen
        $screenFinder = new ScreensInScreen();
        $nestedScreens = $screenFinder->referencesToExport($taskScreen);

        $nestedScreenIds = array_map(function($screen) {
            return $screen[1];
        }, $nestedScreens);

        if (in_array($screen->id, $nestedScreenIds)) {
            return true;
        }

        return false;
    }
}
