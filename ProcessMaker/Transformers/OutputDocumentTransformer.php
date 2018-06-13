<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\OutputDocument;

/**
 * OutputDocument transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class OutputDocumentTransformer extends TransformerAbstract
{

    /**
     * Transform the Output Document.
     *
     * @param OutputDocument $item
     *
     * @return array
     */
    public function transform(OutputDocument $item)
    {
        $data = $item->toArray();
        unset($data['id'], $data['process_id'], $data['open_type'], $data['created_at'], $data['updated_at']);
        return $data;
    }
}
