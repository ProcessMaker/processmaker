<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Trigger;

/**
 * Trigger transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class TriggerTransformer extends TransformerAbstract
{

    /**
     * Transform the trigger.
     *
     * @param Trigger $trigger
     *
     * @return array
     */
    public function transform(Trigger $trigger)
    {
        return [
            'uid' => $trigger->uid,
            'title' => $trigger->title,
            'description' => $trigger->description,
            'type' => $trigger->type,
            'webbot' => $trigger->webbot,
            'param' => $trigger->param,
        ];
    }
}
