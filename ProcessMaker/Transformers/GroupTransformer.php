<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Group;

class GroupTransformer extends TransformerAbstract
{

    /**
     * Transform a Group model to an api consumable
     */
    public function transform(Group $group)
    {
        $data = $group->toArray();
        // Include total_users element
        $data['total_users'] = $group->users()->count();
        return $data;
    }
}
 