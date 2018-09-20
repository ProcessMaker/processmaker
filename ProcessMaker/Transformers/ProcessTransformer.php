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
        'category',
        'user',
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
     * Include Category
     *
     * @param Process $process
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeCategory(Process $process)
    {
        return empty($process->category)
            ? $this->null()
            : $this->item($process->category, new ProcessCategoryTransformer);
    }

    /**
     * Include User
     *
     * @param \ProcessMaker\Transformers\ProcessRequestToken $token
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeUser(ProcessRequestToken $token)
    {
        return empty($token->user)
            ? $this->null()
            : $this->item($token->user, new UserTransformer);
    }
}
