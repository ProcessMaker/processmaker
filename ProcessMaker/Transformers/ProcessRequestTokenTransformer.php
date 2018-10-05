<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Models\ProcessRequestToken;

/**
 * ProcessRequestToken transformer
 * 
 * @package ProcessMaker\Transformers
 */
class ProcessRequestTokenTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'user',
        'request'
    ];

    /**
     * Transform the ProcessRequestToken.
     *
     * @param ProcessRequestToken $token
     *
     * @return array
     */
    public function transform(ProcessRequestToken $token)
    {
        return $token->toArray();
    }

    /**
     * Include Request
     *
     * @param ProcessRequestToken $token
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeRequest(ProcessRequestToken $token)
    {
        return empty($token->processRequest)
            ? $this->null()
            : $this->item($token->processRequest, new ProcessRequestTransformer);
    }

    /**
     * Include User
     *
     * @param ProcessRequestToken $token
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
