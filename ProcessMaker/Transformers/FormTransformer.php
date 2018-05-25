<?php
namespace ProcessMaker\Transformers;
use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Form;
/**
 * Form transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class FormTransformer extends TransformerAbstract
{
    /**
     * Transform the activity.
     *
     * @param Form $item
     *
     * @return array
     */
    public function transform(Form $item)
    {
        $data = $item->toArray();
        unset($data['id'], $data['process_id'], $data['created_at'], $data['updated_at']);
        return $data;
    }
}