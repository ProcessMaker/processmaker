<?php
namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;

/**
 * Process transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class ProcessTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'category'
    ];

    /**
     * Transform the process.
     *
     * @param Process $process
     *
     * @return array
     */
    public function transform(Process $process)
    {
        return $process->toArray();
    }

    /**
     * Include Author
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeCategory(Process $process)
    {
        return $this->item($process->category, new ProcessCategoryTransformer);
    }
}
