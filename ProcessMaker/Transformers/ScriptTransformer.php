<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Script;

/**
 * Trigger transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class ScriptTransformer extends TransformerAbstract
{

    /**
     * Transform the trigger.
     *
     * @param Script $trigger
     *
     * @return array
     */
    public function transform(Script $trigger)
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
