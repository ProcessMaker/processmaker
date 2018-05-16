<?php

namespace ProcessMaker\Transformers;

use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;

/**
 * Assignee transformer, used to prepare the JSON response returned in the
 * Task assigned endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class AssigneeTransformer extends TransformerAbstract
{

    /**
     * Transform the task assignee.
     *
     * @param Collection $assignee
     *
     * @return array
     */
    public function transform($assignee)
    {
        return [
            'uid' => $assignee->assign_uid,
            'name' => $assignee->assign_name,
            'lastname' => $assignee->assign_lastname,
            'username' => $assignee->assign_username,
            'type' => $assignee->assign_type
        ];
    }
}
