<?php

namespace ProcessMaker;

use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\User;

class ApplyRecommendation
{
    public function run(Recommendation $recommendation, User $user, array $params = [])
    {
        $query = $recommendation->baseQuery($user);

        foreach ($query->get() as $task) {
            foreach ($recommendation->actions as $action) {
                switch($action) {
                    case 'mark_as_priority':
                        $task->update(['is_priority' => true]);
                        break;

                    case 'reassign_to_user':
                        $task->reassign($params['to_user_id'], $user);
                        break;

                    default:
                        break;
                }
            }
        }
    }
}