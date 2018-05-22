<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;

class UserTransformer extends TransformerAbstract
{

    /**
     * Transform a User model to an api consumable
     */
    public function transform(User $user)
    {
        $data = $user->toArray();
        // Now, convert our role_id
        $role = $data['role_id'] ? Role::find($data['role_id'])->first() : null;
        $data['role'] = $role ? $role->code : null;
        unset($data['role_id']);
        // Return transformed
        return $data;
    }
}
