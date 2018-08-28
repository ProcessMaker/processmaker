<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\ProcessCategory;

class ProcessCategoryTransformer extends TransformerAbstract
{

    /**
     * Transform a ProcessCategory model to an api consumable
     */
    public function transform(ProcessCategory $processCategory)
    {
        $data = $processCategory->toArray();
        if (!array_key_exists('processes_count', $data)) {
            $data['processes_count'] = $processCategory->processes->count();
        }
        return $data;
    }
}
 