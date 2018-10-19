<?php
namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestCategory;

/**
 * ProcessRequest transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class ProcessRequestTransformer extends TransformerAbstract
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
     * Transform the processRequest.
     *
     * @param ProcessRequest $processRequest
     *
     * @return array
     */
    public function transform(ProcessRequest $processRequest)
    {
        return $processRequest->toArray();
    }

    /**
     * Include user
     *
     * @param ProcessRequest $processRequest
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeUser(ProcessRequest $processRequest)
    {
        return empty($processRequest->getUser())
            ? $this->null()
            : $this->item($processRequest->getUser(), new UserTransformer);
    }

    /**
     * Include processRequest
     *
     * @param ProcessRequest $processRequest
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeProcess(ProcessRequest $processRequest)
    {
        return empty($processRequest->getProcess())
            ? $this->null()
            : $this->item($processRequest->getProcess(), new ProcessTransformer);
    }
}
