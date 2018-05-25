<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Application;

/**
 * Application transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class ApplicationSingleTransformer extends TransformerAbstract
{

    /**
     * Transform the Application.
     *
     * @param Application $item
     *
     * @return array
     */
    public function transform(Application $item)
    {
        $data = $item->toArray();
        unset($data['id'], $data['process_id'], $data['created_at'], $data['updated_at']);
        return $data;
    }
}
