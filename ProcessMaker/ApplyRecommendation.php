<?php

namespace ProcessMaker;

use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Events\TasksUpdated;
use ProcessMaker\Jobs\GenerateUserRecommendations;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ApplyActionNotification;

class ApplyRecommendation
{
    private $errors = [];

    public function run(string $action, Recommendation $recommendation, User $user, array $params = [])
    {
        $errorMessage = null;
        $query = $recommendation->baseQuery($user);

        foreach ($query->get() as $task) {
            switch($action) {
                case 'mark_as_priority':
                    $task->update(['is_priority' => true]);
                    break;

                case 'reassign_to_user':
                    $toUser = User::active()->find($params['to_user_id']);
                    if (!$toUser) {
                        $errorMessage = __('No user selected');
                        break;
                    }
                    try {
                        $task->reassign($toUser->id, $user);
                    } catch(AuthorizationException $e) {
                        $this->errors[] = $e->getMessage();
                    }
                    break;

                default:
                    break;
            }
        }

        if (!empty($this->errors)) {
            $count = count($this->errors);
            $errorMessage =
                ':count tasks were not reassigned because the task settings prevent them from being reassigned';
            $errorMessage = __($errorMessage, ['count' => $count]);
            $user->notify(
                new ApplyActionNotification($errorMessage)
            );
        }

        // Reload the user's tasks list
        event(new TasksUpdated($user->id, $errorMessage));

        // Generate recommendations again so updated tasks are considered
        GenerateUserRecommendations::dispatch($user->id);
    }
}
