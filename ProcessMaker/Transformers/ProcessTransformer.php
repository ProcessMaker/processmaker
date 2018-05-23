<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Process;

/**
 * Process transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class ProcessTransformer extends TransformerAbstract
{
    /**
     * Transform the process.
     *
     * @param Process $process
     *
     * @return array
     */
    public function transform(Process $process)
    {
        $data = $process->toArray();
        // Now we have the associative array form of process
        // But we need to grab the category and insert it in there if it's defined
        if($data['category_id']) {
            // Category is set, let's include the category
            $category = ProcessCategory::find($data['category_id'])->first();
            $data['category'] = $category ? $category->name : '';
        }
        // Unset category_id, we don't need it anymore
        unset($data['category_id']);
        return $data;
    }

}
