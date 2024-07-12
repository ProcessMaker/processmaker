<?php

namespace ProcessMaker;

use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Jobs\GenerateUserRecommendations;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ApplyActionNotification;

class ApplyRecommendation
{
    private $errors = [];

    public function run(string $action, Recommendation $recommendation, User $user, array $params = [])
    {
        $query = $recommendation->baseQuery($user);

        foreach ($query->get() as $task) {
            switch($action) {
                case 'mark_as_priority':
                    $task->update(['is_priority' => true]);
                    break;

                case 'reassign_to_user':
                    try {
                        $task->reassign($params['to_user_id'], $user);
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
            $message = ':count tasks were not reassigned because the task settings prevent them from being reassigned';
            $user->notify(
                new ApplyActionNotification(__($message, ['count' => $count]))
            );
        }

        // Generate recommendations again so updated tasks are considered
        GenerateUserRecommendations::dispatch($user->id);
    }
}
