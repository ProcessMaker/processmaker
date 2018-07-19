<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;

class RequestsTransformer extends TransformerAbstract
{

    /**
     * Transform a list of request for the API
     *
     * @param $requests
     *
     * @return
     */
    public function transform($requests)
    {
        $data = $requests->toArray();
        // Return transformed
        return $data;
    }
}