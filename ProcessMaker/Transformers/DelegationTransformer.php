<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Delegation;

/**
 * Delegation transformer
 * 
 * @package ProcessMaker\Transformers
 */
class DelegationTransformer extends TransformerAbstract
{

    /**
     * Transform the delegation.
     *
     * @param Delegation $delegation
     *
     * @return array
     */
    public function transform(Delegation $delegation)
    {
        return $delegation->toArray();
    }
}
