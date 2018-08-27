<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\User;

class UserTransformer extends TransformerAbstract
{

    /**
     * Transform a User model to an api consumable
     */
    public function transform(User $user)
    {
        // Return transformed
        return $user->toArray();
    }
}
