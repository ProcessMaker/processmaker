<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Role;

class RoleTransformer extends TransformerAbstract
{

    /**
     * Transform a Role model to an api consumable
     */
    public function transform(Role $role)
    {
        $data = $role->toArray();
        // Include total_users element
        $data['total_users'] = $role->users()->count();
        return $data;
    }
}
 