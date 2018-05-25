<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Task;

/**
 * Task transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class TaskTransformer extends TransformerAbstract
{

    /**
     * Transform the Task.
     *
     * @param Task $item
     *
     * @return array
     */
    public function transform(Task $item)
    {
        $data = $item->toArray();
        unset($data['id'], $data['process_id'], $data['created_at'], $data['updated_at']);
        return $data;
    }
}
